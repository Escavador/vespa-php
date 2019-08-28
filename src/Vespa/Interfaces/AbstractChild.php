<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

abstract class AbstractChild extends VespaResult
{
    protected $id;
    protected $relevance;
    protected $fields;

    public function __construct(object $child, $only_raw = false)
    {
        parent::__construct(json_encode($child), $only_raw);
        //if json cannot be decoded
        if($child === null) throw new \Exception("Invalid format to response child.");
    }
}
