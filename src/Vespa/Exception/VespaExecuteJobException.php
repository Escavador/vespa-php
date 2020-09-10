<?php

namespace Escavador\Vespa\Exception;

class VespaExecuteJobException extends VespaException
{
    protected $document_type;
    protected $job_class;

    public function __construct(string $job_class, string $document_type, \Exception $exception = null, string $message = null)
    {
        if (!$message) {
            parent::__construct("[{$document_type}] The job $job_class process failed", $exception);
        } else {
            parent::__construct("[{$document_type}] $job_class - $message", $exception);
        }

        $this->code = 600;
        $this->document_type = $document_type;
        $this->job_class = $job_class;
    }
}
