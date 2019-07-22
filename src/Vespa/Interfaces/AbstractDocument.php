<?php

namespace Escavador\Vespa\Interfaces;

interface AbstractDocument
{

	public function getVespaNamespace();
	public function getVespaDocumentType();
	public function getVespaDocumentId();
	public function getVespaDocumentFields();
    public static function getVespaDocumentTable();
    public static function getVespaDocumentsToIndex(int $limit);
}
