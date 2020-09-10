<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

class DocumentChild extends AbstractChild
{
    protected $source;
    protected $document_definition;
    protected $document;
    protected $fields;
    protected $hits;

    public function __construct(object $child, $only_raw = false)
    {
        parent::__construct($child, $only_raw);
        $this->parseChild($child);
    }

    public function document() : AbstractDocument
    {
        return $this->document;
    }

    public final function hits() : array
    {
        if (!$this->hits)
        {
            $open_tag = config('vespa.default.tags.open', '<hi>');
            $close_tag = config('vespa.default.tags.close', '</hi>');
            $source = json_encode(json_decode($this->raw()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            preg_match_all("|(?<=$open_tag).*(?=$close_tag)|U", $source, $out, PREG_PATTERN_ORDER);
            $out = $out[0];
            $this->hits = array_intersect_key($out, array_unique(array_map('strtolower', $out)));
        }

        return $this->hits;
    }

    public function documentDefinition() : DocumentDefinition
    {
        return $this->document_definition;
    }

    private function parseChild($child)
    {
        $this->id = $child->id;
        $this->relevance = $child->relevance;
        $this->source = $child->source;
        $this->fields = $child->fields;
        $this->document_definition = DocumentDefinition::schemeToDocument($child->fields->documentid);
        if(!$this->document_definition || !$this->document_definition->getModelClass())
        {
            $e = new VespaException("Could not find a document definition for this Vespa response.");
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
        $model_class = $this->document_definition->getModelClass();
        $this->document = $model_class::instanceByVespaChildResponse($this);
    }
}
