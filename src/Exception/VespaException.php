<?php

namespace Escavador\Vespa\Exception;

class VespaException extends \Exception
{
    public function __construct($message, \Exception $exception = null)
    {
        parent::__construct($message, 001, $exception);
    }
}
