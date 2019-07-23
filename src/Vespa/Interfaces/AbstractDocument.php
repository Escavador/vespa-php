<?php

namespace Escavador\Vespa\Interfaces;

interface AbstractDocument
{
	public function getVespaDocumentId();
	public function getVespaDocumentFields();
    public static function markAsIndexed($documents);
    public static function getVespaDocumentsToIndex(int $limit);
}
