<?php

namespace Escavador\Vespa\Exception;

class VespaFailSearchException extends VespaException
{
    protected $payload;

    public function __construct(array $data, \Exception $exception = null)
    {
        parent::__construct("An error occurred while performing the search on Vespa. Payload: " . json_encode($data), $exception);

        $this->code = 300;
        $this->payload = $data;
    }
}
