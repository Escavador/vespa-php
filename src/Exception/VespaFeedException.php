<?php

namespace Escavador\Vespa\Exception;

class VespaFeedException extends VespaException
{
    protected $model;

    public function __construct(string $model, \Exception $exception = null, string $message = null)
    {
        $previous_message = $exception ? "\n{$exception->getMessage()}" : "";

        if (!$message) {
            parent::__construct("[{$model}] Feed process failed.$previous_message", $exception);
        } else {
            parent::__construct("[{$model}] $message$previous_message", $exception);
        }

        $this->code = 600;
        $this->model = $model;
    }
}
