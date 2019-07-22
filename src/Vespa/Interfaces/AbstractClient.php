<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Interfaces\AbstractDocument;

abstract class AbstractClient
{

    abstract public function sendDocuments(array $documents);
	abstract public function sendDocument(AbstractDocument $document);

}
