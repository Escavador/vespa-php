<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Models\Namespace;
use Escavador\Vespa\Models\DocumentType;

abstract class AbstractClient
{

    abstract public function sendDocuments($documents);
	abstract public function sendDocument(AbstractDocument $document);

	protected $host;
	protected $namespaces;

	public function __construct($host= null)
    {
    	$this->host = $host?: trim(config('vespa.host'));
    	$this->namespaces = config('vespa.namespace', []);
    } 

	public final function getNamespace(string $namespace)
	{

	}

	public final function getDocumentTypes(Namespace $namespace)
	{

	}

	public final function getDocumentType(Namespace $namespace, string $documentType)
	{

	}
}
