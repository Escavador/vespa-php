<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractChild;

class GroupChild extends AbstractChild
{
    protected $label;
    protected $fields;
    protected $children;

    public function __construct(object $child, $only_raw = false)
    {
        parent::__construct($child, $only_raw);
        $this->id = $child->id;
        $this->relevance = $child->relevance;
        $this->label = $child->relevance;
        $this->parseChildren($child);
    }

    public function children()
    {
        return $this->children;
    }

    public function child(string $id)
    {
        return isset($this->children[$id]) ? $this->children[$id] : null;
    }

    private function parseChildren($root)
    {
        $this->children = [];
        if (isset($root->children)) {
            foreach ($root->children as $child) {
                $this->children[$child->id] = $child;
            }
        }
    }
}
