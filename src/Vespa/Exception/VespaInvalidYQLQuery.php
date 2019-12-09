<?php
namespace Escavador\Vespa\Exception;

class VespaInvalidYQLQuery extends VespaException
{
    public function __construct($message)
    {
        $this->message = "Could not instantiate query from YQL. $message";
    }
}
