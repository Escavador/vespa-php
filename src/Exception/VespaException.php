<?php

namespace Escavador\Vespa\Exception;

class VespaException extends \Exception
{
    public function __construct($message, \Exception $exception = null)
    {
        $previous_message = $exception ? "\n{$exception->getMessage()}" : "";

        parent::__construct("{$message}{$previous_message}", 001, $exception);
    }
}
