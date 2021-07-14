<?php

namespace Escavador\Vespa\Exception;

class VespaExecuteJobException extends VespaException
{
    protected $document_type;
    protected $job_class;

    public function __construct(string $job_class, string $document_type, \Exception $exception = null, string $message = null)
    {
        $previous_message = $exception ? "\n{$exception->getMessage()}" : "";

        if (!$message) {
            parent::__construct("[{$document_type}] The job $job_class process failed.$previous_message", $exception);
        } else {
            parent::__construct("[{$document_type}] $job_class - $message$previous_message", $exception);
        }

        $this->code = 600;
        $this->document_type = $document_type;
        $this->job_class = $job_class;
    }
}
