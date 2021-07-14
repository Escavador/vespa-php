<?php

namespace Escavador\Vespa\Exception;

class VespaInvalidHostException extends VespaException
{
    public function __construct(\Exception $exception = null)
    {
        $previous_message = $exception ? " {$exception->getMessage()}" : "";

        parent::__construct("Invalid Vespa Host.$previous_message", $exception);

        $this->code = 700;
    }
}
