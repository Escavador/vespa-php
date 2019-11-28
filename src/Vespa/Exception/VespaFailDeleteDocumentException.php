<?php
namespace Escavador\Vespa\Exception;

class VespaFailDeleteDocumentException extends VespaException
{
    protected $definition;

    public function __construct($definition, $scheme, $code, $message)
    {
        $this->code = $code;
        $this->definition = $definition;
        $this->message = "[{$definition->getDocumentType()}]: An error occurred while deleting the document to the scheme : $scheme.".
                         "Exception Message: $message";
    }
}
