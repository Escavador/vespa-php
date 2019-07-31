<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

class SearchResult extends VespaResult
{
    protected $relevance;
    protected $coverage;
    protected $children = [];
    protected $fields;

    public function __construct(string $result, $only_raw = false)
    {
        parent::__construct($result, $only_raw);

        if($this->only_raw)
            return;

        try
        {
            $result = json_decode($result);
            //if json cannot be decoded
            if ($result === null) throw new \Exception("Invalid response");
            $result = (object)$result;

            $this->id = $result->root->id;
            $this->relevance = $result->root->relevance;
            $this->coverage = $result->root->coverage;
            $this->fields = $result->root->fields;
            $this->children = $this->parseChildren($result->root->children);
            $this->only_raw = false;
        }
        catch (\Exception $ex) //TOOD Custom Exception
        {
            $this->only_raw = true;
        }
    }

    //public function

    public function children() : array
    {
        return $this->getAttribute('children');
    }

    private function parseChildren(array $children) : array
    {
        $children_processed = [];

        foreach ($children as $child)
        {
            $children_processed[] = new Child(json_encode($child), $this->only_raw, $child);
        }

        return $children_processed;
    }
}
