<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Models\DocumentDefinition;
use Escavador\Vespa\Models\DocumentNamespace;
use Escavador\Vespa\Models\DocumentType;

abstract class AbstractClient
{
    //abstract public function documentIsRegistred(DocumentDefinition $definition) : bool;
    abstract public function sendDocuments(DocumentDefinition $definition, $documents);
	abstract public function sendDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function searchRaw(string $term, $document_type = null, $options = null);
    abstract public function search(string $term, AbstractDocument $document = null, $options = null);
    abstract public function removeDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function updateDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function getDocument(string $scheme) : AbstractDocument;
    abstract public function getDocuments(DocumentDefinition $definition, AbstractDocument $document);

    protected $host;

	public function __construct()
    {
    	$this->host = Utils::vespaHost();
    	$this->documents = DocumentDefinition::loadDefinition();
    }
}
