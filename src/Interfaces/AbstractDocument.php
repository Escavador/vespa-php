<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Interfaces\AbstractChild;

interface AbstractDocument
{
    /**
     * Return the document id used in Vespa
     *
     * @return string
     */
    public function getVespaDocumentId(): string;
    public function getVespaDocumentFields();
    public static function markAsVespaIndexed(array $document_ids);
    public static function markAsVespaNotIndexed(array $document_ids);
    public static function instanceByVespaChildResponse(AbstractChild $child): AbstractDocument;
    public static function getVespaDocumentsToIndex(int $limit, array $document_ids = null);
    public static function getVespaDocumentIdsToIndex(int $limit);
}
