<?php

namespace Escavador\Vespa\Exception;

class VespaInvalidHostException extends VespaException
{
    public function __construct(\Exception $exception = null)
    {
        parent::__construct("Invalid Vespa Host", $exception);

        $this->code = 700;
    }
}
