<?php

namespace Escavador\Vespa\Exception;

use Escavador\Vespa\Models\DocumentDefinition;

class VespaFailGetDocumentException extends VespaException
{
    protected $definition;
    protected $scheme;

    public function __construct(DocumentDefinition $definition, string $scheme, \Exception $exception)
    {
        parent::__construct("[{$definition->getDocumentType()}]: An error occurred while getting the document to the scheme: $scheme", $exception);

        $this->code = $exception->getCode();
        $this->definition = $definition;
        $this->scheme = $scheme;
    }
}
