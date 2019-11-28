<?php
namespace Escavador\Vespa\Exception;

class VespaInvalidHostException extends VespaException
{
    public function __construct()
    {
        $this->message = 'Invalid Vespa Host';
    }
}
