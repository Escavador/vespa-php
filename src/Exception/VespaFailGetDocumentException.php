<?php

namespace Escavador\Vespa\Exception;

use Escavador\Vespa\Models\DocumentDefinition;

class VespaFailGetDocumentException extends VespaException
{
    protected $definition;
    protected $scheme;

    public function __construct(DocumentDefinition $definition, string $scheme, \Exception $exception)
    {
        $previous_message = " {$exception->getMessage()}" ?? "";
        parent::__construct("[{$definition->getDocumentType()}]: An error occurred while getting the document to the scheme: $scheme.$previous_message", $exception);

        $this->code = $exception->getCode();
        $this->definition = $definition;
        $this->scheme = $scheme;
    }
}
