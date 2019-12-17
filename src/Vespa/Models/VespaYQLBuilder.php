<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Interfaces\VespaResult;

class VespaYQLBuilder
{
    public final function view() : string
    {
        return $this->__toString();
    }

    public final function reset() : VespaYQLBuilder
    {
        $vars = get_object_vars($this);
        $properties = array_keys($vars);

        foreach ($properties as $property)
        {
            unset($this->$property);
        }

        return $this;
    }

    public function addStemmingCondition(string $term, bool $stemming, string $field) : VespaYQLBuilder
    {
        $term = $this->removeQuotes($term);
        return $this->createCondition($field, "CONTAINS",  "([{'stem': ".json_encode($stemming)."}]'$term')");
    }

    public function addStemmingGroupCondition(string $term, bool $stemming = false, string $field, $operator = 'CONTAINS', $group_name = null, $logical_operator = 'OR') : VespaYQLBuilder
    {
        $term = $this->removeQuotes($term);
        return $this->createGroupCondition($field, $operator, "([{'stem': ".json_encode($stemming)."}]'$term')", $group_name, $logical_operator);
    }

    public function addWandCondition(string $term, string $field = 'default', $group_name = null, int $target_num_hits = null, $score_threshold = null, $logical_operator = 'AND') : VespaYQLBuilder
    {
        $tokens = $this->splitTerm($term);
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);

        if($tokens == 0)
        {
            throw new VespaInvalidYQLQuery("");
        }

        $this->createWand($tokens, $field, $group_name, $target_num_hits, $score_threshold, $logical_operator);

        if($not_tokens != null && count($not_tokens) > 0)
        {
            $this->createWand($not_tokens, $field, "NOT 1", $target_num_hits, $score_threshold, "AND !");
        }

        return $this;
    }

    public function addWeakAndCondition(string $term, string $field = 'default', $group_name = null, int $target_num_hits = null, $score_threshold = null, $logical_operator = 'AND') : VespaYQLBuilder
    {
        $tokens = $this->splitTerm($term);
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);

        if($tokens == 0)
        {
            throw new VespaInvalidYQLQuery("");
        }

        $this->createWeakAnd("phrase('".implode($tokens, "', '")."')", $field, $group_name, $target_num_hits, $score_threshold, $logical_operator);

        if($not_tokens != null && count($not_tokens) > 0)
        {
            $not_tokens = "phrase('".implode($not_tokens, "', '")."')";
            $this->createWeakAnd($not_tokens, $field, "NOT 1", $target_num_hits, $score_threshold, "AND !");
        }

        return $this;
    }

    public function addTokenizeCondition(string $term, string $field = 'default', $group_name = null, $logical_operator = "AND", bool $stemming = null) : VespaYQLBuilder
    {
        $term = $this->removeQuotes($term);
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);

        $not_group_name = null;
        $not_logical_operator = 'AND !';
        for($i = 0; $i < count($not_tokens); $i ++)
        {
            $this->createGroupCondition($field, "CONTAINS", $not_tokens[$i], $not_group_name, $not_logical_operator);
            if($i == 0)
            {
                $not_group_name = -1; //always the last group added
                $not_logical_operator = 'AND';  //only the first logical operator can be different of 'OR'
            }
        }

        if(count($tokens) == 0)
        {
            $term = "'$term'";
            if($stemming !== null) $term = "([{'stem': ". json_encode($stemming). "}]$term)";
            $this->createGroupCondition($field, "CONTAINS", $term, $group_name, $logical_operator);
        }

        for($i = 0; $i < count($tokens); $i ++)
        {
            $key = "'".$tokens[$i]."'";

            if($stemming !== null)
            {
                $key = "([{'stem': ". json_encode($stemming). "}]$key)";
            }

            $this->createGroupCondition($field, "CONTAINS", $key, $group_name, $logical_operator);
            if($i == 0)
            {
                $logical_operator = 'OR';  //only the first logical operator can be different of 'OR'
                $group_name = -1; //always the last group added
            }
        }

        return $this;
    }

    public function addPhraseCondition(string $term, string $field = 'default', $group_name = null, $logical_operator = "AND") : VespaYQLBuilder
    {
        $term = $this->removeQuotes($term);
        $tokens = $this->generateCombinations($term);

        if(count($tokens) == 0)
        {
            $this->createGroupCondition($field, "CONTAINS", "'$term'", $group_name, $logical_operator);
        }

        $phrase = '(';
        for($i = 0; $i < count($tokens); $i ++)
        {
            $token = $tokens[$i];
            $key = "'" . key($token) . "'";
            $phrase .= $key . (($i < count($tokens) - 1)? ', ': '');
        }
        $phrase .= ')';

        $this->createGroupCondition($field, "CONTAINS", $phrase, $group_name, $logical_operator);

        return $this;
    }

    public function addDistanceCondition(string $term, string $field = 'default', int $word_distance = null,
                                         $group_name = null, $logical_operator = "AND", bool $stemming = null,
                                         $same_order = true) : VespaYQLBuilder
    {
        [$tokens, $not_tokens] = $this->tokenizeTerm($term, true);

        if(!isset($word_distance)) $word_distance = count($tokens) - 1;

        //if only one token is passed, adds a simple condition
        if(count($tokens) == 1)
        {
            $aux_term = implode(' ', $tokens);
            if($stemming !== null) $aux_term = "([{'stem': ". json_encode($stemming). "}]$aux_term)";
            $this->createGroupCondition($field, "CONTAINS", $aux_term, $group_name, $logical_operator);
            return $this;
        }

        $near = $same_order ? 'onear' : 'near';
        $stemming_term = '';
        if($stemming !== null) $stemming_term = ", 'stem': ". json_encode($stemming);
        $this->createGroupCondition($field, "CONTAINS", "([ {'distance': ".($word_distance + 1)."$stemming_term}]$near('".implode("', '", $tokens)."'))", $group_name, $logical_operator);

        return $this;
    }

    public function addCondition($term, string $field = 'default', $operator = 'CONTAINS') : VespaYQLBuilder
    {
        switch (gettype($term))
        {
            case "boolean": $term = json_encode($term); break;
            case "string": {
                $term = $this->removeQuotes($term);
                $term = "'$term'";
            } break;
        }

        return $this->createCondition($field, $operator, $term);
    }

    public function addRawCondition($condition) : VespaYQLBuilder
    {
        if(!isset($this->search_conditions)) $this->search_conditions = [];

        $this->search_conditions [] = $condition;
        return $this;
    }

    public function addGroupCondition($term, string $field = 'default', $operator = 'CONTAINS', $group_name = null, $logical_operator = 'OR', bool $stemming = null) : VespaYQLBuilder
    {
        $term = $this->removeQuotes($term);

        switch (gettype($term))
        {
            case "boolean": $term = json_encode($term); break;
            case "string": {
                $term = "'$term'";

                if($stemming !== null)
                    $term = "([ {'stem': ". json_encode($stemming). "}]$term)";
            } break;
        }

        return $this->createGroupCondition($field, $operator, $term, $group_name, $logical_operator);
    }

    public function addField(string $field) : VespaYQLBuilder
    {
        if(!isset($this->fields))
            $this->fields = [];

        $this->fields[] = $field;

        return $this;
    }

    public function addSource(string $source) : VespaYQLBuilder
    {
        if(!isset($this->sources))
            $this->sources = [];

        if(!in_array($source, $this->sources)) $this->sources[] = $source;

        return $this;
    }

    public function addDocumentType(string $document_type) : VespaYQLBuilder
    {
        if(!isset($this->document_types))
            $this->document_types = [];

        if(!in_array($document_type, $this->document_types)) $this->document_types[] = $document_type;

        return $this;
    }

    public function limit(int $limit) : VespaYQLBuilder
    {
        $this->limit = strval($limit);

        return $this;
    }

    public function offset(int $offset) : VespaYQLBuilder
    {
        $this->offset = strval($offset);

        return $this;
    }

    public function orderBy($field, $order = "ASC") : VespaYQLBuilder
    {
        if(strtoupper($order) != "DESC" and strtoupper($order) != "ASC")
        {
            // Custom Exception
            throw new \Exception("Syntax Error. The property \"order by\" should be \"DESC\" or \"ASC\"");
        }

        if(!isset($this->orderBy))
            $this->orderBy = [];

        $this->orderBy[$field] =  strtoupper($order);

        return $this;
    }

    public function __toString()
    {
        $limit = isset($this->limit)? $this->limit : null;
        $offset = isset($this->offset)? $this->offset : null;
        $fields = isset($this->fields)? implode(', ', $this->fields) : '*';
        $document_types = isset($this->document_types)? ("(sddocname CONTAINS '".implode("' OR  sddocname CONTAINS '", $this->document_types)."')" ): null;
        $search_conditions = $document_types? [$document_types] : [];
        $weakand_groups = isset($this->weakand_groups)?: [];
        $wand_groups = isset($this->wand_groups)?: [];
        $search_condition_groups = [];
        $sources = '';
        if(!isset($this->sources) || count($this->sources) === 0)
        {
            $sources = ' SOURCES *';
        }
        else
        {
            if (count($this->sources) > 1)
                $sources = ' SOURCES ';

            $sources .= implode(', ', $this->sources);
        }

        $orderBy = null;
        if(isset($this->orderBy) && count($this->orderBy) > 0)
        {
            $aux_orderBy = [];
            foreach ($this->orderBy as $key => $value)
            {
                $aux_orderBy[] = "$key $value";
            }

            $orderBy = "  ORDER BY " . implode(", ", $aux_orderBy);
        }

        $yql = "SELECT $fields FROM $sources ";
        if(isset($this->search_conditions)) $search_conditions = array_merge($search_conditions, $this->search_conditions);
        if(isset($this->search_condition_groups))
        {
            $start = true;
            foreach ($this->search_condition_groups as $search_condition_group)
            {
                $group = $search_condition_group;
                $group_condition = '';
                for ($j = 0; $j < count($group); $j++)
                {
                    $condition = $group[$j];
                    if($j === 0)
                    {
                        //The first group logical operator is used to join the group with other conditions (or group conditions)
                        //It is only placed if other conditions exists.
                        $group_condition .= (!$search_conditions && $start === true ? '' : $condition[0])." (";
                        $group_condition .= " $condition[1] $condition[2] $condition[3] ";
                    }
                    else if($j < count($group))
                    {
                        $group_condition .= implode(" ", $condition) . ' ';
                    }

                    if($j == (count($group) - 1))
                    {
                        $group_condition .= " ) ";
                    }
                }
                $search_condition_groups[] = $group_condition. ' ';
                $start = false;
            }
        }
        if($search_conditions || $search_condition_groups || $weakand_groups) $yql .= " WHERE ";
        if($search_conditions) $yql .= implode(' AND ', $search_conditions)." ";
        if($search_condition_groups) $yql .= implode(' ', $search_condition_groups)." ";
        $yql .= $this->formatWeakAndGroups($search_conditions || $search_condition_groups);
        $yql .= $this->formatWandGroups($search_conditions || $search_condition_groups || $weakand_groups);
        if($orderBy != null) $yql .= $orderBy;
        if($limit != null) $yql .= " LIMIT $limit";
        if($offset != null) $yql .= " OFFSET $offset";

        $yql = $this->removeExtraSpace($yql .= ';');
        return $yql;
    }

    private function formatWandGroups($condition_before = false)
    {
        $formatedWandGroups = [];
        if(!isset($this->wand_groups))
        {
            $this->wand_groups = [];
        }

        $str_group = "";

        foreach ($this->wand_groups as $group)
        {
            if($condition_before) {
                $str_group = $group["head"]["logical_operator"];
            }

            $str_group .= " ";
            $tags = "";
            if(isset($group["head"]["target_num_hits"]))
            {
                $tags .= "{'targetNumHits': {$group["head"]["target_num_hits"]}}";
            }
            if(isset($group["head"]["score_threshold"]))
            {
                if($tags != "")
                {
                    $tags .= ",";
                }

                $tags .= "{'scoreThreshold': {$group["head"]["score_threshold"]}}";
            }
            if($tags != "")
            {
                $str_group .= "[$tags]";
            }

            foreach ($group["body"] as $key => $values)
            {
                $str_group .= "( wand($key, ";
                $group_body = [];
                foreach ($values[0] as $value)
                {
                    $weigth = intval(100 * count($values) / (count($group_body) + 1));
                    if(is_numeric($value))
                    {
                        $group_body[] = "[$value, $weigth]";
                        $isNumeric = true;
                    }
                    else
                    {
                        $group_body[] = "'$value': $weigth";
                        $isNumeric = false;
                    }
                }
                if($isNumeric)
                {
                    $str_group .= "[".implode(", ", $group_body)."]";
                }
                else
                {
                    $str_group .= "{".implode(", ", $group_body)."}";
                }
                $str_group .= "))";
            }

            $formatedWandGroups[] = $str_group;
            $condition_before = true;
        }

        return implode("", $formatedWandGroups);
    }

    private function formatWeakAndGroups($condition_before = false)
    {
        $formatedWeakAndGroups = [];
        if(!isset($this->weakand_groups))
        {
            $this->weakand_groups = [];
        }

        $str_group = "";

        foreach ($this->weakand_groups as $group)
        {
            if($condition_before) {
                $str_group = $group["head"]["logical_operator"];
            }

            $str_group .= " (";
            $tags = "";
            if(isset($group["head"]["target_num_hits"]))
            {
                $tags .= "{'targetNumHits': {$group["head"]["target_num_hits"]}}";
            }
            if(isset($group["head"]["score_threshold"]))
            {
                if($tags != "")
                {
                    $tags .= ",";
                }

                $tags .= "{'scoreThreshold': {$group["head"]["score_threshold"]}}";
            }
            if($tags != "")
            {
                $str_group .= "[$tags]";
            }
            $str_group .= "weakAnd( ";
            $group_body = [];
            foreach ($group["body"] as $value)
            {
                $group_body[] = "$value[0] $value[1] $value[2]";
            }
            $str_group .= implode(", ", $group_body);
            $str_group .= "))";

            $formatedWeakAndGroups[] = $str_group;
            $condition_before = true;
        }

        return implode("", $formatedWeakAndGroups);
    }

    private function createWand(array $term, string $field = 'default', $group_name = null, int $target_num_hits = null, double $score_threshold = null, $logical_operator = 'AND')
    {
        if(!isset($this->weakand_groups)) $this->wand_groups = [];
        if($group_name === null) $group_name = $this->createGroupName($this->wand_groups);
        if(is_string($group_name) && is_numeric($group_name)) $group_name = intval($group_name);
        while (array_key_exists($group_name, $this->wand_groups)) $name++;
        $size = count($this->wand_groups);

        if($group_name < 0 && $size > 0) //put condition in the last group created
        {
            $group_name = array_keys($this->wand_groups)[$size - 1];
        }
        else if($group_name === null || $group_name === '' || $group_name < 0) //add new group
        {
            $group_name = $size;
        }

        $this->wand_groups[$group_name]["head"]["logical_operator"] = $logical_operator;
        if($target_num_hits > 0) $this->wand_groups[$group_name]["head"]["target_num_hits"] = $target_num_hits;
        if($score_threshold > 0) $this->wand_groups[$group_name]["head"]["score_threshold"] = $score_threshold;
        $this->wand_groups[$group_name]["body"][$field][] = $term;

        return $this;
    }

    private function createWeakAnd(string $term, string $field = 'default', $group_name = null, int $target_num_hits = null, double $score_threshold = null, $logical_operator = 'AND')
    {
        $operator = 'CONTAINS';
        if(!isset($this->weakand_groups)) $this->weakand_groups = [];
        if($group_name === null) $group_name = $this->createGroupName($this->weakand_groups);
        if(is_string($group_name) && is_numeric($group_name)) $group_name = intval($group_name);
        while (array_key_exists($group_name, $this->weakand_groups)) $name++;
        $size = count($this->weakand_groups);

        if($group_name < 0 && $size > 0) //put condition in the last group created
        {
            $group_name = array_keys($this->weakand_groups)[$size - 1];
        }
        else if($group_name === null || $group_name === '' || $group_name < 0) //add new group
        {
            $group_name = $size;
        }

        $this->weakand_groups[$group_name]["head"]["logical_operator"] = $logical_operator;
        if($target_num_hits > 0) $this->weakand_groups[$group_name]["head"]["target_num_hits"] = $target_num_hits;
        if($score_threshold > 0) $this->weakand_groups[$group_name]["head"]["score_threshold"] = $score_threshold;
        $this->weakand_groups[$group_name]["body"][] = [$field, $operator, $term];

        return $this;
    }

    private function createGroupCondition(string $field, string $operator, $term, $group_name= null, $logical_operator)
    {
        if(!isset($this->search_condition_groups)) $this->search_condition_groups = [];
        if($group_name === null) $group_name = $this->createGroupName($this->search_condition_groups);
        if(is_string($group_name) && is_numeric($group_name)) $group_name = intval($group_name);
        $size = count($this->search_condition_groups);

        if($group_name < 0 && $size > 0) //put condition in the last group created
        {
            $key = array_keys($this->search_condition_groups)[ $size - 1];
            $this->search_condition_groups[$key][] = [$logical_operator, $field, $operator, $term];
        }
        else if($group_name === null || $group_name === '' || $group_name < 0) //add new group
        {
            $this->search_condition_groups[$size][] = [$logical_operator, $field, $operator, $term];
        }
        else //add new group if group_name does not exists, otherwise put condition inside it
        {
            $this->search_condition_groups[$group_name][] = [$logical_operator, $field, $operator, $term];
        }

        return $this;
    }

    private function createGroupName($group)
    {
        if (!isset($group)) return 0;
        $name = count($group);
        while (array_key_exists($name, $group)) $name++;
        return $name;
    }

    private function createCondition(string $field, string $operator, $term) : VespaYQLBuilder
    {
        if(!isset($this->search_conditions)) $this->search_conditions = [];

        $this->search_conditions [] = "$field $operator $term";
        return $this;
    }

    private function removeSQLInjection(string $text) : string
    {
        return str_replace('"', "\"", str_replace("'", "\'", $text));
    }

    private function removeSpecial(sting $text) : string
    {
        return $this->removeExtraSpace(str_replace('  ', '', preg_replace('/[#$%^&*()+=\-\[\]\';,.\/{}|":<>?~\\\\]/', '${1} ', $text)));
    }

    private function removeQuotes(string $text) : string
    {
        $text = preg_replace('/^"(.*)"$/i', '${1}', $text);
        return preg_replace("/^'(.*)'$/i", '${1}', $text);
    }

    private function removeExtraSpace(string $text) : string
    {
        return trim(preg_replace("/\s+/", '${1} ', $text));
    }

    private function splitTerm($term)
    {
        return  explode(" ", $term);
    }

    private function tokenizeTerm($term, $separate_tokens = false)
    {
        $tokens = $this->splitTerm($term);

        if(!$separate_tokens)
        {
            return $tokens;
        }

        $aux_tokens = [];
        $not_tokens = [];
        for($i = 0; $i < count($tokens); $i ++)
        {
            $tokens[$i] = $this->removeQuotes($tokens[$i]);

            //If the token is empty, ignore it
            if($tokens[$i] == '')
            {
                continue;
            }

            //if the token start with minus signal, put it in another array
            if(strpos($tokens[$i], '-') === 0 && strlen($tokens[$i]) > 1)
            {
                $not_tokens[] = "'".substr($tokens[$i], 1, strlen($tokens[$i]))."'";
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

        for($i = 0; $i < count($arr); $i++)
        {
            for ($j = $i + 1 ; $j < count($arr); $j++)
            {
                if (!isset($arr_c[$arr[$i]])) $arr_c[$arr[$i]] = [];
                if (!isset($arr_c[$arr[$j]])) $arr_c[$arr[$j]] = [];

                if (!in_array($arr[$j], $arr_c[$arr[$i]]))
                {
                    $arr_c[$arr[$i]][] = $arr[$j];
                    $tuples[] = [$arr[$i] => $arr[$j]];
                }
                if ($inverse && !in_array($arr[$i], $arr_c[$arr[$j]]))
                {
                    $arr_c[$arr[$j]][] = $arr[$i];
                    $tuples[] = [$arr[$j] => $arr[$i]];
                }
            }
        }
        return $tuples;
    }
}
