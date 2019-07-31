<?php

namespace Escavador\Vespa\Interfaces;

interface AbstractDocument
{
    public function getVespaDocumentId();
	public function getVespaDocumentFields();
    public static function markAsVespaIndexed($documents);
    public static function instanceByVespaChildResponse(VespaResult $result) : AbstractDocument;
    public static function getVespaDocumentsToIndex(int $limit);
}
