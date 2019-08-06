<?php

namespace Escavador\Vespa\Models;

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

    public function addStemmingCondition(string $term, bool $stemming, string $field = 'default') : VespaYQLBuilder
    {
        return $this->createCondition($field, "CONTAINS",  "([{'stem': ".json_encode($stemming)."}]'$term')");
    }

    public function addDistanceCondition(string $term, string $field = 'default', int $word_distance = null) : VespaYQLBuilder
    {
        if(!isset($word_distance)) $word_distance = config('vespa.default.word_distance', 2);
        $tokens = $this->generateCombinations($term);
        foreach ($tokens as $token)
        {
            $key = key($token);
            $value = $token[$key];
            $this->createCondition($field, "CONTAINS", "([ {'distance': ".($word_distance + 1)."} ]onear('$key', '$value'))");
        }

        return $this;
    }

    public function addCondition($term, string $operator = 'CONTAINS', string $field = 'default') : VespaYQLBuilder
    {
        switch (gettype($term))
        {
            case "boolean": $term = json_encode($term); break;
            case "string": $term = "$term"; break;
        }

        return $this->createCondition($field, $operator, $term);
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

        $this->sources[] = $source;

        return $this;
    }

    public function addDocumentType(string $document_type) : VespaYQLBuilder
    {
        if(!isset($this->document_types))
            $this->document_types = [];

        $this->document_types[] = $document_type;

        return $this;
    }

    public function limit(int $limit) : VespaYQLBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset) : VespaYQLBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    public function __toString()
    {
        $limit = isset($this->limit)? $this->limit : null;
        $offset = isset($this->offset)? $this->offset : null;
        $fields = isset($this->fields)? implode(', ', $this->fields) : '*';
        $sources = isset($this->sources)? implode(', ', $this->sources) : '*';
        $document_types = isset($this->document_types)? ("sddocname CONTAINS '".implode("' AND CONTAINS '", $this->document_types)."'" ): null;
        $search_conditions = $document_types? [$document_types] : [];
        $yql = "SELECT $fields FROM SOURCES $sources";

        if(isset($this->search_conditions)) $search_conditions = array_merge($search_conditions, $this->search_conditions);
        if($search_conditions) $yql .= " WHERE ".implode(' AND ', $search_conditions)." ";
        if($limit) $yql .= " LIMIT $limit";
        if($offset) $yql .= " OFFSET $offset";

        $yql = $this->removeExtraSpace($yql .= ';');
        return $yql;
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
        return preg_replace("/^'(.*)'$/i", '${1}', preg_replace('/^"(.*)"$/i', '${1}', $query));
    }

    private function removeExtraSpace(string $text) : string
    {
        return trim(preg_replace("/\s+/", '${1} ', $text));
    }

    private function generateCombinations($term)
    {
        $arr = explode(" ", $term);
        $arr_c = [];
        $tuples = [];

        for($i = 0; $i < count($arr); $i++)
        {
            for ($j = $i + 1 ; $j < count($arr); $j++)
            {
                if (!isset($arr_c[$arr[$i]])) $arr_c[$arr[$i]] = [];
                if (!isset($arr_c[$arr[$j]])) $arr_c[$arr[$j]] = [];

                if (!in_array($arr[$i], $arr_c[$arr[$j]]))
                {
                    $arr_c[$arr[$j]][] = $arr[$i];
                    $tuples[] = [$arr[$j] => $arr[$i]];
                }
                if (!in_array($arr[$j], $arr_c[$arr[$i]]))
                {
                    $arr_c[$arr[$i]][] = $arr[$j];
                    $tuples[] = [$arr[$i] => $arr[$j]];
                }
            }
        }
        return $tuples;
    }
}
