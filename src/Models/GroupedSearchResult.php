<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\VespaResult;

class GroupedSearchResult extends VespaResult
{
    protected $relevance;
    protected $coverage;
    protected $group_root;
    protected $children_document = [];
    protected $fields;

    public function __construct(string $result, $only_raw = false)
    {
        parent::__construct($result, $only_raw);

        if ($this->only_raw) {
            return;
        }

        try {
            $result = (object)json_decode($result);
            $this->id = $result->root->id;
            $this->relevance = $result->root->relevance;
            $this->coverage = $result->root->coverage;
            $this->fields = $result->root->fields;
            $this->parseChildren($result->root);
            $this->only_raw = false;
        } catch (\Exception $ex) { //TOOD Custom Exception
            $this->only_raw = true;
        }
    }

    public function allChildren(): array
    {
        $children_group = $this->childrenGroup();
        $children_document = $this->childrenDocument();

        return array_merge($children_group ?: [], $children_document ?: []);
    }

    public function groupRoot(): GroupRoot
    {
        return $this->group_root ?: null;
    }

    public function groups(): array
    {
        $group_root = $this->groupRoot();
        return $group_root ? $group_root->groups() : [];
    }

    public function children(): array
    {
        return $this->getAttribute('children_document') ?: [];
    }

    private function parseChildren($root)
    {
        if (isset($root->children)) {
            foreach ($root->children as $child) {
                if (strpos($child->id, "group") === 0) {
                    $this->group_root = new GroupRoot($child, $this->only_raw);
                } else {
                    $this->children_document[] = new DocumentChild($child, $this->only_raw);
                }
            }
        }
    }
}
