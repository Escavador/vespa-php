<?php

namespace Escavador\Vespa\Interfaces;

interface class AbstractDocument
{

	public function getVespaNamespace();
	public function getVespaDocumentType();
	public function getVespaDocumentId();
	public function getVespaDocumentFields();
	public static function getVespaDocumentsToIndex(int $limit);
}