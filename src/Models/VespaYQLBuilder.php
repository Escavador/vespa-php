<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Exception\VespaInvalidYQLQueryException;

class VespaYQLBuilder
{
    public function __construct()
    {
        $this->search_condition_groups = [];
        $this->document_type = [];
        $this->used_document_type = [];
    }

    final public function view(): string
    {
        return $this->__toString();
    }

    final public function reset(): VespaYQLBuilder
    {
        $vars = get_object_vars($this);
        $properties = array_keys($vars);

        foreach ($properties as $property) {
            unset($this->$property);
        }

        return $this;
    }

    final public function hasWhereConditions(): string
    {
        return !empty($this->search_condition_groups);
    }

    public function addStemmingCondition(string $term, bool $stemming = false, string $field = 'default', $operator = 'CONTAINS', $logical_operator = 'AND', $group_name = null): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $allowed_operators = ['CONTAINS', 'PHRASE'];

        if (strtoupper($operator) == "CONTAINS") {
            return $this->createGroupCondition($field, $operator, "'$term'", $logical_operator, $group_name, ['stem' => $stemming], $allowed_operators);
        } else //PHRASE
        {
            $tokens = $this->splitTerm(addslashes($term));
            return $this->createGroupCondition($field, $operator, "('" . implode("', '", $tokens) . "')", $logical_operator, $group_name, ['stem' => $stemming], $allowed_operators);
        }

        return $this;
    }

    public function addWandCondition(string $term, string $field = 'default', $logical_operator = 'AND', $group_name = null, int $target_num_hits = null, float $score_threshold = null, \Closure $weight_tokens = null): VespaYQLBuilder
    {
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);
        $tokens = $this->generateWeightedTokens($tokens);
        if ($weight_tokens != null) {
            $tokens = $weight_tokens($tokens);
        }
        $this->createWand($tokens, $field, $group_name, $target_num_hits, $score_threshold, $logical_operator);

        if (count($not_tokens) > 0) {
            if ($weight_tokens != null) {
                $not_tokens = $weight_tokens($not_tokens);
            }
            $this->createWand($not_tokens, $field, null, $target_num_hits, null, "AND !");
        }
        return $this;
    }

    public function addWeakAndCondition(array $tokens, string $field = 'default', $logical_operator = 'AND', $group_name = null, int $target_num_hits = null, int $score_threshold = null): VespaYQLBuilder
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
        $this->createWeakAnd($parsed_tokens, $field, $group_name, $target_num_hits, $score_threshold, $logical_operator);
        return $this;
    }

    public function addPhraseCondition(string $term, string $field = 'default', $group_name = null, $logical_operator = "AND"): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $tokens = $this->tokenizeTerm($term);

        if (count($tokens) == 1) {
            $this->createGroupCondition($field, "CONTAINS", "'$term'", $logical_operator, $group_name);
        } else {
            $this->createGroupCondition($field, "PHRASE", $tokens, $logical_operator, $group_name);
        }
        return $this;
    }

    public function addDistanceCondition(
        string $term,
        string $field = 'default',
        int $word_distance = 3,
        $group_name = null,
        $logical_operator = "AND",
        bool $stemming = false,
        $same_order = true
    ): VespaYQLBuilder {
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
                $this->addCondition($aux_term, $field, $group_name, $logical_operator);
            }
            if (count($not_tokens) == 1) {
                $aux_term = implode(' ', $not_tokens);
                $this->addCondition($aux_term, $field, $group_name, $logical_operator);
            }


            return $this;
        }

        $operator_options["distance"] = $word_distance;
        $operator = $same_order ? "ONEAR" : "NEAR";

        if (count($tokens) > 1) {
            $this->createGroupCondition($field, $operator, $tokens, $logical_operator, $group_name, $operator_options);
        }
        if (count($not_tokens) > 1) {
            $this->createGroupCondition($field, $operator, $not_tokens, "AND !", $group_name, $operator_options);
        }
        return $this;
    }

    public function addRangeCondition(int $start, int $end, string $field = 'default', string $logical_operator = "AND", $group_name = null)
    {
        return $this->createGroupCondition($field, 'RANGE', [$start, $end], $logical_operator, $group_name);
    }

    public function addNumericCondition($term, string $field = 'default', string $operator = '=', string $logical_operator = "AND", $group_name = null): VespaYQLBuilder
    {
        $allowed_operators = ["=", ">", "<", "<=", ">="];
        if (!is_numeric($term)) {
            throw new VespaInvalidYQLQueryException("The variable '\$term' ({$term}) must be numeric.");
        }
        return $this->createGroupCondition($field, $operator, $term, $logical_operator, $group_name, null, $allowed_operators);
    }

    public function addBooleanCondition(bool $term, string $field = 'default', string $logical_operator = "AND", $group_name = null): VespaYQLBuilder
    {
        $term = json_encode($term);
        return $this->createGroupCondition($field, "=", $term, $logical_operator, $group_name);
    }

    public function addRawCondition($condition, $group_name = null, $logical_operator = 'AND'): VespaYQLBuilder
    {
        $condition = Utils::removeQuotes($condition);
        return $this->addSearchConditionGroup($logical_operator, [$condition], $group_name);
    }

    public function addCondition(string $term, string $field = 'default', $group_name = null, $logical_operator = 'AND', $operator = 'CONTAINS'): VespaYQLBuilder
    {
        $term = Utils::removeQuotes($term);
        $allowed_operators = ['CONTAINS', 'MATCHES'];

        return $this->createGroupCondition($field, $operator, "'$term'", $logical_operator, $group_name);
    }

    public function addField(string $field): VespaYQLBuilder
    {
        if (!isset($this->fields)) {
            $this->fields = [];
        }

        $this->fields[] = $field;

        return $this;
    }

    public function addSource(string $source): VespaYQLBuilder
    {
        $source = Utils::removeQuotes($source);
        if (!isset($this->sources)) {
            $this->sources = [];
        }

        if (!in_array($source, $this->sources)) {
            $this->sources[] = $source;
        }

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

        if (!isset($this->orderBy)) {
            $this->orderBy = [];
        }

        $this->orderBy[$field] = strtoupper($order);

        return $this;
    }

    public function __toString()
    {
        $limit = isset($this->limit) ? $this->limit : null;
        $offset = isset($this->offset) ? $this->offset : null;
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

        // add document type conditions
        $document_type_group = 0;
        foreach ($this->document_type as $doc_type) {
            if (in_array($doc_type, $this->used_document_type)) {
                continue;
            }
            $logical_operator = $document_type_group == 0 ? "AND" : "OR";
            $group_name = $document_type_group == 0 ? null : -1;
            $this->addCondition($doc_type, 'sddocname', $group_name, $logical_operator);
            $this->used_document_type[] = $doc_type;
            $document_type_group++;
        }

        $search_condition_groups = $this->search_condition_groups;
        $yql = "SELECT $fields FROM $sources ";
        $has_condition = false;
        foreach ($search_condition_groups as $search_conditions) {
            if (!$has_condition) {
                $yql .= " WHERE ";
            }
            $index = 0;
            foreach ($search_conditions as $search_condition) {
                if (count($search_condition["condition"]) == 1) {
                    $condition = $search_condition["condition"][0];
                } else {
                    $operator = $search_condition["condition"][2];
                    switch (strtoupper($operator)) {
                        case "=":
                            $condition = implode(" ", $search_condition["condition"]);
                            break;
                        case "PHRASE":
                            $condition = $this->formatPhraseCondition($search_condition["condition"]);
                            break;
                        case "WAND":
                            $condition = $this->formatWandCondition($search_condition["condition"]);
                            break;
                        case "WEAKAND":
                            $condition = $this->formatWeakAndCondition($search_condition["condition"]);
                            break;
                        case "NEAR":
                        case "ONEAR":
                            $condition = $this->formatNearCondition($search_condition["condition"]);
                            break;
                        default:
                            $condition = "({$search_condition["condition"][0]} {$search_condition["condition"][2]} ({$search_condition["condition"][1]} {$search_condition["condition"][3]}))";
                    }
                }
                if ($has_condition) {
                    $yql .= " {$search_condition["logical_operator"]} ";
                } else {
                    if ($search_condition["logical_operator"] == "AND!") {
                        $yql .= " ! ";
                    }
                    $has_condition = true;
                }
                if ($index == 0) {
                    $yql .= "(";
                }
                $yql .= " {$condition} ";
                $index++;
            }
            $yql .= ")";
        }
        if ($orderBy != null) {
            $yql .= $orderBy;
        }
        if ($limit != null) {
            $yql .= " LIMIT $limit";
        }
        if ($offset != null) {
            $yql .= " OFFSET $offset";
        }

        return Utils::removeExtraSpace($yql .= ';');
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

    private function formatNearCondition(array $condition): string
    {
        return "({$condition[0]} contains ({$condition[1]} {$condition[2]} (\"" . implode("\", \"", $condition[3]) . "\")))";
    }

    private function createWand(array $term, string $field = 'default', $group_name = null, int $target_num_hits = null, float $score_threshold = null, $logical_operator = 'AND'): VespaYQLBuilder
    {
        $wand_option = [];
        if ($target_num_hits !== null) {
            $wand_option["targetNumHits"] = $target_num_hits;
        }
        if ($score_threshold !== null) {
            $wand_option["scoreThreshold"] = $score_threshold;
        }
        return $this->createGroupCondition($field, 'WAND', $term, $logical_operator, $group_name, $wand_option);
    }

    private function createWeakAnd(array $tokens, string $field = 'default', $group_name = null, int $target_num_hits = null, int $score_threshold = null, $logical_operator = 'AND')
    {
        $weakand_option = [];
        if ($target_num_hits !== null) {
            $wand_option["targetNumHits"] = $target_num_hits;
        }
        if ($score_threshold !== null) {
            $wand_option["scoreThreshold"] = $score_threshold;
        }
        $term = "(" . implode(", ", $tokens) . ")";
        $this->createGroupCondition($field, 'WEAKAND', $term, $logical_operator, $group_name, $weakand_option);
        return $this;
    }

    private function createGroupCondition(string $field, string $operator, $term, $logical_operator, $group_name = null, array $operator_options = null, $allowed_operators = null)
    {
        $parsed_operator_options = "";
        if ($operator_options !== null) {
            $parsed_operator_options = json_encode($operator_options, JSON_PRESERVE_ZERO_FRACTION);
            $parsed_operator_options = "[$parsed_operator_options]";
        }
        $this->validateCommonRules($term, $operator, $logical_operator, $allowed_operators);
        $condition = [$field, $parsed_operator_options, strtolower($operator), $term];
        $this->addSearchConditionGroup($logical_operator, $condition, $group_name);
        return $this;
    }

    private function addSearchConditionGroup($logical_operator, $condition, $group_name = null)
    {
        if ($logical_operator == "NOT") {
            $logical_operator = "AND!";
        }
        $group_name = $this->validateGroupName($group_name);
        $this->search_condition_groups[$group_name][] = ['logical_operator' => $logical_operator, 'condition' => $condition];
        return $this;
    }

    private function getLastGroupName()
    {
        $size = count($this->search_condition_groups);
        if ($size > 0) {
            return array_keys($this->search_condition_groups)[$size - 1];
        }
        return 0;
    }

    private function createGroupName()
    {
        if (count($this->search_condition_groups) == 0) {
            return "0";
        }
        $name = count($this->search_condition_groups);
        while (array_key_exists($name, $this->search_condition_groups)) {
            $name++;
        }
        return strtolower($name);
    }

    private function validateGroupName($group_name)
    {
        if ($group_name == null) {
            $group_name = $this->createGroupName();
        }
        if ($group_name < 0) {
            $group_name = $this->getLastGroupName();
        }
        return strtolower((string)($group_name));
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
                if (!isset($arr_c[$arr[$i]])) {
                    $arr_c[$arr[$i]] = [];
                }
                if (!isset($arr_c[$arr[$j]])) {
                    $arr_c[$arr[$j]] = [];
                }

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
        if ((gettype($tokens) == "array" && (count($tokens) == 0 || in_array('', $tokens))) || (gettype($tokens) == "string" && ($tokens == '' || $tokens == "''" || $tokens == '""'))) {
            throw new VespaInvalidYQLQueryException("There must be at least one token to be searched.");
        }
        if ($logical_operator == "NOT") {
            $logical_operator = "AND!";
        }
        $allowed_logical_operators = ["AND", "OR", "AND!"];
        if (!in_array(str_replace(" ", "", strtoupper($logical_operator)), $allowed_logical_operators)) {
            throw new VespaInvalidYQLQueryException("The logical operator {$logical_operator} doen't exists. The allowed logical operators are: " . implode(", ", $allowed_logical_operators) . ".");
        }

        if ($allowed_operators == null || count($allowed_operators) == 0) {
            $allowed_operators = ["CONTAINS", "PHRASE", "MATCHES", "=", "NEAR", "ONEAR", "WAND", "WEAKAND", "EQUIV",
                ">", "<", "<=", ">=", "SAMEELEMENT", "EQUIV", "PREDICATE", "NONEMPTY", "RANGE"];
        }

        if (!in_array(strtoupper($operator), $allowed_operators)) {
            throw new VespaInvalidYQLQueryException("The operator {$operator} is not supported by this method or it doesn't exist. The allowed operators are: " . implode(", ", $allowed_operators) . ".");
        }
        return true;
    }

    protected $search_condition_groups;
    protected $document_type;
}
