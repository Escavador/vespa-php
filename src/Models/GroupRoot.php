<?php

namespace Escavador\Vespa\Models;

class GroupRoot
{
    protected $id;
    protected $relevance;
    protected $continuation;
    protected $groups;

    public function __construct(object $root, $only_raw = false)
    {
        $this->id = $root->id;
        $this->relevance = $root->relevance;
        $this->continuation = $root->continuation;
        $this->parseChildren($root, $only_raw);
    }

    public function groups(): array
    {
        return $this->groups ?: [];
    }

    private function parseChildren($root, $only_raw)
    {
        $this->groups = [];
        if (isset($root->children)) {
            foreach ($root->children as $child) {
                $this->groups[$child->id] = new GroupChild($child, $only_raw);
            }
        }
    }
}
