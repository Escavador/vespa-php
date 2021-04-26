<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Models\DocumentDefinition;

abstract class AbstractClient
{
    //abstract public function documentIsRegistred(DocumentDefinition $definition) : bool;
    abstract public function search(array $data): VespaResult;

    abstract public function sendDocuments(DocumentDefinition $definition, $documents);

    abstract public function sendDocument(DocumentDefinition $definition, AbstractDocument $document);

    abstract public function updateDocument(DocumentDefinition $definition, AbstractDocument $document);

    abstract public function removeDocument(string $scheme);

    abstract public function getDocument(string $scheme): VespaResult;

    protected $host;
    protected $headers;

    public function __construct()
    {
        $this->host = Utils::vespaHost();
        $this->refreshDefinitions();
        $this->headers = config('vespa.default.headers', [
            'Content-Type' => 'application/json',
        ]);
    }

    public final function refreshDefinitions()
    {
        $this->documents = DocumentDefinition::loadDefinition();
    }
}
