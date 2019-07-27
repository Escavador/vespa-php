<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Models\Child;

interface AbstractDocument
{
    public function getVespaDocumentId();
	public function getVespaDocumentFields();
    public static function markAsVespaIndexed($documents);
    public static function instanceByVespaChildResponse(Child $child) : AbstractDocument;
    public static function getVespaDocumentsToIndex(int $limit);
}
