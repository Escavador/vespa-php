<?php
namespace Escavador\Vespa\Exception;

use Escavador\Vespa\Models\DocumentDefinition;

class VespaFailGetDocumentException extends VespaException
{
    protected $definition;

    public function __construct(DocumentDefinition $definition, $scheme, $code, $message)
    {
        $this->code = $code;
        $this->definition = $definition;
        $this->message = "[{$definition->getDocumentType()}]: An error occurred while getting the document to the scheme : $scheme.".
                         "Exception Message: $message";
    }
}
