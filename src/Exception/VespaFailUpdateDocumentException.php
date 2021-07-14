<?php

namespace Escavador\Vespa\Exception;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Models\DocumentDefinition;

class VespaFailUpdateDocumentException extends VespaException
{
    protected $document;
    protected $definition;

    public function __construct(DocumentDefinition $definition, AbstractDocument $document, \Exception $exception = null)
    {
        $previous_message = $exception ? " {$exception->getMessage()}" : "";

        parent::__construct("[{$definition->getDocumentType()}]: Document {$document->getVespaDocumentId()} was not updated to Vespa.$previous_message", $exception);

        $this->code = 500;
        $this->document = $document;
        $this->definition = $definition;
    }
}
