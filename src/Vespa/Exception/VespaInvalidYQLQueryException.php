<?php
namespace Escavador\Vespa\Exception;

class VespaInvalidYQLQueryException extends VespaException
{
    public function __construct(\Exception $exception = null)
    {
        parent::__construct("Could not instantiate query from YQL.", $exception);

        $this->code = 800;
    }
}
