<?php
namespace Escavador\Vespa\Exception;

class VespaException extends \Exception
{
    public function __construct($message)
    {
        $this->code = 1;
        $this->message = $message;
    }
}
