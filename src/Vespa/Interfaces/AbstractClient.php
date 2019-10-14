<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Models\DocumentDefinition;
use Escavador\Vespa\Models\DocumentNamespace;
use Escavador\Vespa\Models\DocumentType;
use Escavador\Vespa\Models\VespaQuery;

abstract class AbstractClient
{
    //abstract public function documentIsRegistred(DocumentDefinition $definition) : bool;
    abstract public function search(array $data) : VespaResult;
    abstract public function sendDocuments(DocumentDefinition $definition, $documents);
	abstract public function sendDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function updateDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function removeDocument(string $scheme);
    abstract public function getDocument(string $scheme) : AbstractDocument;

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
