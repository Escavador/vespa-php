<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\AbstractDocument;

abstract class AbstractClient
{

	abstract public function sendDocuments(array AbstractDocument $documents);
	abstract public function sendDocument(AbstractDocument $document);

}