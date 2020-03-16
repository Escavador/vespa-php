<?php
namespace Escavador\Vespa\Exception;

class VespaFeedException extends VespaException
{
    protected $model;

    public function __construct(string $model, \Exception $exception=null, string $message=null)
    {
        if(!$message)
        {
            parent::__construct("[{$model}] Feed process failed", $exception);
        }
        else
        {
            parent::__construct("[{$model}] $message", $exception);
        }

        $this->code = 600;
        $this->model = $model;
    }
}
