<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Exception\VespaInvalidYQLQueryException;

class VespaYQLBuilder
{
    public function __construct()
    {
        $this->search_condition_groups = [];
        $this->last_group = &$this->createGroup($this->search_condition_groups);
        $this->document_type = [];
        $this->used_document_type = [];
    }

    public final function view(): string
    {
        return $this->__toString();
    }

    public final function reset(): VespaYQLBuilder
    {
        $vars = get_object_vars($this);
        $properties = array_keys($vars);

        foreach ($properties as $property) {
            unset($this->$property);
        }

        return $this;
    }

    public final function hasWhereConditions(): bool
    {
        return !empty($this->search_condition_groups);
    }

    public function addConditionGroup(\Closure $closure): VespaYQLBuilder
    {
        $last_group = &$this->last_group; // save the last group

        // create a new group and refresh the last group
        if(isset($this->last_group['conditions'])) {
            // If the group has "conditions" key, it's a subgroup in a group
            $this->last_group = &$this->createGroup($this->last_group['conditions']);
        } else {
            $this->last_group = &$this->createGroup($this->last_group);
        }

        $closure($this); // execute function with more clause in new group
        $this->last_group = &$last_group; // put the old last group back
        return $this;
    }

    public function addStemmingCondition(string $term, bool $stemming = false, string $field = 'default', $operator = 'CONTAINS', $logical_operator = 'AND'): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $allowed_operators = ['CONTAINS', 'PHRASE'];

        if (strtoupper($operator) == "CONTAINS") {
            return $this->createGroupCondition($field, $operator, "'$term'", $logical_operator, ['stem' => $stemming], $allowed_operators);
        } else {  //PHRASE
            $tokens = $this->splitTerm(addslashes($term));
            return $this->createGroupCondition($field, $operator, "('" . implode("', '", $tokens) . "')", $logical_operator, ['stem' => $stemming], $allowed_operators);
        }

        return $this;
    }

    public function addWandCondition(string $term, string $field = 'default', $logical_operator = 'AND', int $target_num_hits = null, float $score_threshold = null, \Closure $weight_tokens = null): VespaYQLBuilder
    {
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);
        $tokens = $this->generateWeightedTokens($tokens);
        if ($weight_tokens != null) {
            $tokens = $weight_tokens($tokens);
        }
        $this->createWand($tokens, $field, $target_num_hits, $score_threshold, $logical_operator);

        if (count($not_tokens) > 0) {
            if ($weight_tokens != null) {
                $not_tokens = $weight_tokens($not_tokens);
            }
            $this->createWand($not_tokens, $field, null, $target_num_hits, null, "AND !");
        }
        return $this;
    }

    public function addWeakAndCondition(array $tokens, string $field = 'default', $logical_operator = 'AND', int $target_num_hits = null, int $score_threshold = null): VespaYQLBuilder
    {
        $parsed_tokens = [];
        foreach ($tokens as $token) {
            if (gettype($token) == 'array' && count($token) == 1) {
                $operator = array_key_first($token);
                $term = $token[$operator];
                $term = Utils::removeQuotes($term);

                if ($term == "") {
                    throw new VespaInvalidYQLQueryException("Searching for blank strings is not allowed.");
                }

                if (strcasecmp("CONTAINS", $operator) === 0) {
                    $parsed_tokens[] = "$field $operator '$term'";
                    continue;
                } elseif (strcasecmp("PHRASE", $operator) === 0) {
                    $terms = $this->tokenizeTerm($term);
                    $parsed_tokens[] = "$field $operator('" . implode("', '", $terms) . "')";
                    continue;
                }
            }
            throw new VespaInvalidYQLQueryException("Tokens for the weakAnd operator must be formed by [[operator => term], ...]. Allowed operators are: CONTAINS or PHRASE.");
        }
        $this->createWeakAnd($parsed_tokens, $field, $target_num_hits, $score_threshold, $logical_operator);
        return $this;
    }

    public function addPhraseCondition(string $term, string $field = 'default', $logical_operator = "AND"): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $tokens = $this->tokenizeTerm($term);

        if (count($tokens) == 1) {
            $this->createGroupCondition($field, "CONTAINS", "'$term'", $logical_operator);
        } else {
            $this->createGroupCondition($field, "PHRASE", $tokens, $logical_operator);
        }
        return $this;
    }

    public function addDistanceCondition(string $term, string $field = 'default', int $word_distance = 3,
                                         $logical_operator = "AND", bool $stemming = false,
                                         $same_order = true): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $term = Utils::removeExtraSpace($term);
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);

        $operator_options = [
            'stem' => $stemming
        ];

        //if only one token is passed, adds a simple condition
        if (count($tokens) <= 1 && count($not_tokens) <= 1) {
            if (count($tokens) == 1) {
                $aux_term = implode(' ', $tokens);
                $this->addCondition($aux_term, $field, $logical_operator);
            }
            if (count($not_tokens) == 1) {
                $aux_term = implode(' ', $not_tokens);
                $this->addCondition($aux_term, $field, $logical_operator);
            }

            return $this;
        }

        $operator_options["distance"] = $word_distance;
        $operator = $same_order ? "ONEAR" : "NEAR";

        if (count($tokens) > 1) {
            $this->createGroupCondition($field, $operator, $tokens, $logical_operator, $operator_options);
        }
        if (count($not_tokens) > 1) {
            $this->createGroupCondition($field, $operator, $not_tokens, "AND !", $operator_options);
        }
        return $this;
    }

    public function addRangeCondition(int $start, int $end, string $field = 'default', string $logical_operator = "AND")
    {
        return $this->createGroupCondition($field, 'RANGE', [$start, $end], $logical_operator);
    }

    public function addNumericCondition($term, string $field = 'default', string $operator = '=', string $logical_operator = "AND"): VespaYQLBuilder
    {
        $allowed_operators = ["=", ">", "<", "<=", ">="];
        if (!is_numeric($term)) throw new VespaInvalidYQLQueryException("The variable '\$term' ({$term}) must be numeric.");
        return $this->createGroupCondition($field, $operator, $term, $logical_operator, null, $allowed_operators);
    }

    public function addBooleanCondition(bool $term, string $field = 'default', string $logical_operator = "AND"): VespaYQLBuilder
    {
        $term = json_encode($term);
        return $this->createGroupCondition($field, "=", $term, $logical_operator);
    }

    public function addRawCondition($condition, $logical_operator = 'AND'): VespaYQLBuilder
    {
        $condition = Utils::removeQuotes($condition);
        $this->validateLogicalOperator($logical_operator);
        return $this->addSearchConditionGroup($logical_operator, [$condition]);
    }

    public function addCondition(string $term, string $field = 'default', $logical_operator = 'AND', $operator = 'CONTAINS'): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $allowed_operators = ['CONTAINS', 'MATCHES'];

        return $this->createGroupCondition($field, $operator, "'$term'", $logical_operator);
    }

    public function addField(string $field): VespaYQLBuilder
    {
        if (!isset($this->fields))
            $this->fields = [];

        $this->fields[] = $field;

        return $this;
    }

    public function addSource(string $source): VespaYQLBuilder
    {
        $source = Utils::removeQuotes($source);
        if (!isset($this->sources))
            $this->sources = [];

        if (!in_array($source, $this->sources)) $this->sources[] = $source;

        return $this;
    }

    public function addDocumentType(string $document_type): VespaYQLBuilder
    {
        $document_type = Utils::removeQuotes($document_type);
        if (!in_array($document_type, $this->document_type)) {
            $this->document_type[] = $document_type;
        }
        return $this;
    }

    public function addManyDocumentType(array $document_type): VespaYQLBuilder
    {
        foreach ($document_type as $type) {
            $this->addDocumentType($type);
        }
        return $this;
    }

    public function limit(int $limit): VespaYQLBuilder
    {
        $this->limit = strval($limit);

        return $this;
    }

    public function offset(int $offset): VespaYQLBuilder
    {
        $this->offset = strval($offset);

        return $this;
    }

    public function orderBy($field, $order = "ASC"): VespaYQLBuilder
    {
        if (strtoupper($order) != "DESC" and strtoupper($order) != "ASC") {
            throw new VespaInvalidYQLQueryException("Syntax Error. The property \"order by\" should be \"DESC\" or \"ASC\"");
        }

        if (!isset($this->orderBy))
            $this->orderBy = [];

        $this->orderBy[$field] = strtoupper($order);

        return $this;
    }

    public function __toString()
    {
        $limit = $this->limit ?? null;
        $offset = $this->offset ?? null;
        $fields = isset($this->fields) ? implode(', ', $this->fields) : '*';
        $sources = '';
        if (!isset($this->sources) || count($this->sources) === 0) {
            $sources = ' SOURCES *';
        } else {
            if (count($this->sources) > 1) {
                $sources = ' SOURCES ';
            }
            $sources .= implode(', ', $this->sources);
        }
        $orderBy = null;
        if (isset($this->orderBy) && count($this->orderBy) > 0) {
            $aux_orderBy = [];
            foreach ($this->orderBy as $key => $value) {
                $aux_orderBy[] = "$key $value";
            }

            $orderBy = "  ORDER BY " . implode(", ", $aux_orderBy);
        }

        // Add document type conditions if exits
        if (!empty($this->document_type)) {
            $this->addConditionGroup(function ($yql) {
                $logical_operator = 'AND';
                foreach ($yql->document_type as $doc_type) {
                    // If the document type has already been used
                    if (in_array($doc_type, $yql->used_document_type)) {
                        continue;
                    }

                    $yql->addCondition($doc_type, 'sddocname', $logical_operator);
                    $yql->used_document_type[] = $doc_type;
                    $logical_operator = 'OR';
                }
            });
        }

        $yql = "SELECT $fields FROM $sources ";
        if (!empty($this->search_condition_groups)) {
            $yql .= " WHERE ";
            $yql .= $this->applySearchConditions($this->search_condition_groups[0]);
        }

        if ($orderBy != null) $yql .= $orderBy;
        if ($limit != null) $yql .= " LIMIT $limit";
        if ($offset != null) $yql .= " OFFSET $offset";

        return Utils::removeExtraSpace($yql .= ';');
    }

    private function applySearchConditions(array $search_condition_groups, &$has_condition = false): string
    {
        $yql = "";
        $is_first_condition = true;
        foreach ($search_condition_groups as $search_conditions) {
            // If the user has opened a group without placing conditions
            if (empty($search_conditions)) {
                continue;
            }

            // If $search_conditions is a group of conditions
            if (isset($search_conditions["conditions"])) {
                if (isset($search_conditions['logical_operator'])) {
                    if($search_conditions['logical_operator'] == "AND!" && !$has_condition) {
                        $yql .= "!(";
                    } elseif($has_condition) {
                        $yql .= " {$search_conditions['logical_operator']} (";
                    }
                }
                $yql .= $this->applySearchConditions($search_conditions["conditions"], $has_condition);
                $yql .= ")";
                continue;
            }

            $search_condition = $search_conditions['condition'];
            $logical_operator = $search_conditions['logical_operator'] ?? null;

            if ($logical_operator !== null) {
                if (!$has_condition && $is_first_condition) {
                    // Convert the NOT operator to the correct form when it is at the beginning of the condition
                    if ($logical_operator == "AND!") {
                        $open_group = "";
                        while (substr("$yql", -1, 1) == "(") {
                            $yql = substr($yql, 0, -1);
                            $open_group .= "(";
                        }
                        $yql .= " !$open_group";
                    }
                } elseif ($has_condition && $is_first_condition) {
                    $open_group = "";
                    while (substr("$yql", -1, 1) == "(") {
                        $yql = substr($yql, 0, -1);
                        $open_group .= "(";
                    }
                    $yql .= " $logical_operator $open_group";
                } elseif ($has_condition && !$is_first_condition) {
                    $yql .= " {$logical_operator} ";
                }
            }

            $operator = $search_condition[2] ?? null;
            switch (strtoupper($operator)) {
                case "CONTAINS":
                    $condition = $this->formatContainsCondition($search_condition);
                    break;
                case "PHRASE":
                    $condition = $this->formatPhraseCondition($search_condition);
                    break;
                case "WAND":
                    $condition = $this->formatWandCondition($search_condition);
                    break;
                case "WEAKAND":
                    $condition = $this->formatWeakAndCondition($search_condition);
                    break;
                case "NEAR":
                case "ONEAR":
                    $condition = $this->formatNearCondition($search_condition);
                    break;
                default:
                    // If no options were passed
                    if (count($search_condition) > 1 && empty($search_condition[1])) {
                        unset($search_condition[1]);
                    }
                    $condition = implode(" ", $search_condition);
            }

            $has_condition = true;
            $is_first_condition = false;

            $yql .= "({$condition})";
        }

        return $yql;
    }

    private function formatWandCondition(array $condition): string
    {
        return "({$condition[1]} {$condition[2]}({$condition[0]}, " . json_encode($condition[3]) . "))";
    }

    private function formatWeakAndCondition(array $condition): string
    {
        // TODO Implement
        return "";
    }

    private function formatPhraseCondition(array $condition): string
    {
        // TODO Implement
        return "";
    }

    private function formatContainsCondition(array $condition): string
    {
        return "{$condition[0]} {$condition[2]} ({$condition[1]} {$condition[3]})";
    }

    private function formatNearCondition(array $condition): string
    {
        return "({$condition[0]} contains ({$condition[1]} {$condition[2]} (\"" . implode("\", \"", $condition[3]) . "\")))";
    }

    private function createWand(array $term, string $field = 'default', int $target_num_hits = null, float $score_threshold = null, $logical_operator = 'AND'): VespaYQLBuilder
    {
        $wand_option = [];
        if ($target_num_hits !== null) $wand_option["targetNumHits"] = $target_num_hits;
        if ($score_threshold !== null) $wand_option["scoreThreshold"] = $score_threshold;
        return $this->createGroupCondition($field, 'WAND', $term, $logical_operator, $wand_option);
    }

    private function createWeakAnd(array $tokens, string $field = 'default', int $target_num_hits = null, int $score_threshold = null, $logical_operator = 'AND')
    {
        $weakand_option = [];
        if ($target_num_hits !== null) $wand_option["targetNumHits"] = $target_num_hits;
        if ($score_threshold !== null) $wand_option["scoreThreshold"] = $score_threshold;
        $term = "(" . implode(", ", $tokens) . ")";
        $this->createGroupCondition($field, 'WEAKAND', $term, $logical_operator, $weakand_option);
        return $this;
    }

    private function createGroupCondition(string $field, string $operator, $term, $logical_operator, array $operator_options = null, $allowed_operators = null)
    {
        $parsed_operator_options = "";
        if ($operator_options !== null) {
            $parsed_operator_options = json_encode($operator_options, JSON_PRESERVE_ZERO_FRACTION);
            $parsed_operator_options = "[$parsed_operator_options]";
        }
        $this->validateCommonRules($term, $operator, $logical_operator, $allowed_operators);
        $condition = [$field, $parsed_operator_options, strtolower($operator), $term];
        $this->addSearchConditionGroup($logical_operator, $condition);
        return $this;
    }

    private function addSearchConditionGroup($logical_operator, $condition)
    {
        if ($logical_operator == "NOT") {
            $logical_operator = "AND!";
        }

        $group = &$this->last_group;
        $clause = ['logical_operator' => $logical_operator, 'condition' => $condition];
        $root_group_reference = &$this->search_condition_groups[0];

        // If $group is the root group, is not necessary put logical_operator in the beginning
        if ($root_group_reference === $group) {
            // Removes the logical operator  (except NOT operator) from conditions because it is the first in the group
            if (empty($root_group_reference) && $logical_operator != "AND!") {
                unset($clause['logical_operator']);
            }

            // Put the clause as a next element
            $group[count($group)] = $clause;
        } else {
            // If it's the first condition in the group, take the logical operator to join with other groups
            if (!isset($group['logical_operator'])) {
                $group['logical_operator'] = $logical_operator;

                // Removes the logical operator from conditions because it is the first in the group
                unset($clause['logical_operator']);
            }

            // Put the clause as a subelement of "conditions" array
            $group['conditions'][] = $clause;
        }

        return $this;
    }

    private function &createGroup(&$group)
    {
        $group_name = count($group);
        $group[$group_name] = [];
        $new_group = &$group[$group_name];
        return $new_group;
    }

    private function getLastGroupName()
    {
        $size = count($this->last_group);
        if ($size > 0) return array_keys($this->last_group)[$size - 1];
        return 0;
    }

    private function removeSQLInjection(string $text): string
    {
        return str_replace('"', "\"", str_replace("'", "\'", $text));
    }

    private function splitTerm($term)
    {
        $term = Utils::removeExtraSpace($term);
        return explode(" ", $term);
    }

    private function tokenizeTerm($term, $separate_tokens = false)
    {
        $tokens = $this->splitTerm($term);

        if (!$separate_tokens) {
            return $tokens;
        }

        $aux_tokens = [];
        $not_tokens = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $tokens[$i] = Utils::removeQuotes($tokens[$i]);

            //If the token is empty, ignore it
            if ($tokens[$i] == '') {
                continue;
            }

            //if the token start with minus signal, put it in another array
            if (strpos($tokens[$i], '-') === 0 && strlen($tokens[$i]) > 1) {
                $not_tokens[] = "'" . substr($tokens[$i], 1, strlen($tokens[$i])) . "'";
                continue;
            }

            $aux_tokens[] = $tokens[$i];
        }

        return [$tokens, $not_tokens];
    }

    private function generateCombinations($term, $inverse = false)
    {
        $arr = $this->splitTerm($term);
        $arr_c = [];
        $tuples = [];

        for ($i = 0; $i < count($arr); $i++) {
            for ($j = $i + 1; $j < count($arr); $j++) {
                if (!isset($arr_c[$arr[$i]])) $arr_c[$arr[$i]] = [];
                if (!isset($arr_c[$arr[$j]])) $arr_c[$arr[$j]] = [];

                if (!in_array($arr[$j], $arr_c[$arr[$i]])) {
                    $arr_c[$arr[$i]][] = $arr[$j];
                    $tuples[] = [$arr[$i] => $arr[$j]];
                }
                if ($inverse && !in_array($arr[$i], $arr_c[$arr[$j]])) {
                    $arr_c[$arr[$j]][] = $arr[$i];
                    $tuples[] = [$arr[$j] => $arr[$i]];
                }
            }
        }
        return $tuples;
    }

    private function generateWeightedTokens(array $tokens)
    {
        $weighted_tokens = [];

        foreach ($tokens as $key => $token) {
            $weighted_tokens["$token"] = 1;
        }
        return $weighted_tokens;
    }

    private function validateCommonRules($tokens, $operator, $logical_operator, array $allowed_operators = null): bool
    {
        $this->validateToken($tokens);

        $this->validateLogicalOperator($logical_operator);

        $this->validateOperator($operator, $allowed_operators);

        return true;
    }

    private function validateOperator($operator, array $allowed_operators = null): bool
    {
        if ($allowed_operators == null || count($allowed_operators) == 0) {
            $allowed_operators = ["CONTAINS", "PHRASE", "MATCHES", "=", "NEAR", "ONEAR", "WAND", "WEAKAND", "EQUIV",
                ">", "<", "<=", ">=", "SAMEELEMENT", "EQUIV", "PREDICATE", "NONEMPTY", "RANGE"];
        }

        if (!in_array(strtoupper($operator), $allowed_operators)) {
            throw new VespaInvalidYQLQueryException("The operator {$operator} is not supported by this method or it doesn't exist. The allowed operators are: " . implode(", ", $allowed_operators) . ".");
        }

        return true;
    }

    private function validateToken($tokens): bool
    {
        if ((gettype($tokens) == "array" && (count($tokens) == 0 || in_array('', $tokens))) || (gettype($tokens) == "string" && ($tokens == '' || $tokens == "''" || $tokens == '""'))) {
            throw new VespaInvalidYQLQueryException("There must be at least one token to be searched.");
        }

        return true;
    }

    private function validateLogicalOperator($logical_operator): bool
    {
        if ($logical_operator == "NOT") {
            $logical_operator = "AND!";
        }
        $allowed_logical_operators = ["AND", "OR", "AND!"];
        if (!in_array(str_replace(" ", "", strtoupper($logical_operator)), $allowed_logical_operators)) {
            throw new VespaInvalidYQLQueryException("The logical operator {$logical_operator} doen't exists. The allowed logical operators are: " . implode(", ", $allowed_logical_operators) . ".");
        }

        return true;
    }

    protected $last_group;
    protected $search_condition_groups;
    protected $document_type;
    protected $limit;
    protected $offset;
    protected $used_document_type;
    protected $orderBy;
}
