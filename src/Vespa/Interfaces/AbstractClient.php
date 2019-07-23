<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Models\DocumentDefinition;
use Escavador\Vespa\Models\DocumentNamespace;
use Escavador\Vespa\Models\DocumentType;

abstract class AbstractClient
{

    abstract public function sendDocuments(DocumentDefinition $definition, $documents);
	abstract public function sendDocument(DocumentDefinition $definition, AbstractDocument $document);

	protected $host;

	public function __construct($host= null)
    {
    	$this->host = $host?: trim(config('vespa.host'));
    	$this->documents = DocumentDefinition::loadDefinition();
    }

	public final function getNamespace(string $namespace)
	{

	}

	public final function getDocumentTypes(DocumentNamespace $namespace)
	{

	}

	public final function getDocumentType(DocumentNamespace $namespace, string $documentType)
	{

	}
}
