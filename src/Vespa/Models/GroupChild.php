<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

class GroupChild extends AbstractChild
{
    protected $relevance;
    protected $continuation;
    protected $children;

    public function __construct(object $child, $only_raw = false)
    {
        parent::__construct($child, $only_raw);
        $this->id = $child->id;
        $this->relevance = $child->relevance;
        $this->continuation = $child->continuation;
        $this->children = $child->children;
    }
}
