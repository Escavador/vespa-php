<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

class DocumentChild extends AbstractChild
{
    protected $source;
    protected $document_definition;
    protected $document;

    public function __construct(object $child, $only_raw = false)
    {
        parent::__construct($child, $only_raw);
        $this->parseChild($child);
    }

    public function document() : AbstractDocument
    {
        return $this->document;
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
            //TODO Custom Exception
            throw new \Exception("Could not find a document definition for this Vespa response.");
        }
        $model_class = $this->document_definition->getModelClass();
        $this->document = $model_class::instanceByVespaChildResponse($this);
    }
}
