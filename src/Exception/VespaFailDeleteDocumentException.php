<?php

namespace Escavador\Vespa\Exception;

class VespaFailDeleteDocumentException extends VespaException
{
    protected $definition;
    protected $scheme;

    public function __construct($definition, string $scheme, \Exception $exception = null)
    {
        $previous_message = " {$exception->getMessage()}" ?? "";
        parent::__construct("[{$definition->getDocumentType()}]: An error occurred while deleting the document to the scheme : $scheme.$previous_message", $exception);

        $this->code = 100;
        $this->definition = $definition;
        $this->scheme = $scheme;
    }
}
