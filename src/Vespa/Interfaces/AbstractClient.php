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
    //abstract public function search(string $term, AbstractDocument $document = null, $searchCondition = null);
    abstract public function removeDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function updateDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function getDocument(DocumentDefinition $definition, AbstractDocument $document);
    abstract public function getDocuments(DocumentDefinition $definition, AbstractDocument $document);


    protected $host;

	public function __construct($host= null)
    {
    	$this->host = $host?: trim(config('vespa.host'));
    	$this->documents = DocumentDefinition::loadDefinition();
    }
}
