<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Models\AbstractChild;

interface AbstractDocument
{
    public function getVespaDocumentId();
	public function getVespaDocumentFields();
    public static function markAsVespaIndexed($documents);
    public static function instanceByVespaChildResponse(AbstractChild $child) : AbstractDocument;
    public static function getVespaDocumentsToIndex(int $limit);
}
