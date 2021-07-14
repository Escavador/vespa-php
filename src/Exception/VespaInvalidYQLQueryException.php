<?php

namespace Escavador\Vespa\Exception;

class VespaInvalidYQLQueryException extends VespaException
{
    public function __construct($message = null, \Exception $exception = null)
    {
        $previous_message = $exception ? "\n{$exception->getMessage()}" : "";

        if ($message != null) {
            $message = " $message";
        }
        parent::__construct("Could not instantiate query from YQL.$message${previous_message}", $exception);

        $this->code = 800;
    }
}
