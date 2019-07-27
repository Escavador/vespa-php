<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;

class SearchResult
{
    protected $only_raw;
    protected $id;
    protected $relevance;
    protected $fields;
    protected $coverage;
    protected $children = [];
    protected $json_data;


    public function __construct(string $result, $only_raw = false)
    {
        $this->json_data = $result;
        $this->only_raw = $only_raw;
        if($this->only_raw)
            return;

        $result = json_decode($result);
        //if json cannot be decoded
        if($result === null) {
            throw new \Exception("Invalid response");
        }
        $result = (object) $result;

        try {
            $this->id = $result->root->id;
            $this->relevance = $result->root->relevance;
            $this->fields = $result->root->fields;
            $this->coverage = $result->root->coverage;
            $this->children = $this->parseChildren($result->root->children);
            $this->only_raw = false;
        }
        catch (\Exception $ex) //TOOD Custom Exception
        {
            $this->only_raw = true;
        }
    }

    public function onlyRaw() : bool
    {
        return $this->only_raw;
    }

    public function children() : array
    {
        if($this->onlyRaw())
            //TODO Custom Exeception
            throw new \Exception("This response was not normalized. Please see the \"raw\" property");

        return $this->children;
    }

    public function raw() : string
    {
        return $this->json_data;
    }

    private function parseChildren(array $children) : array
    {
        $children_processed = [];

        foreach ($children as $child)
        {
            $children_processed[] = new Child($child);
        }

        return $children_processed;
    }
}
