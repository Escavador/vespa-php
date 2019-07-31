<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

class Child extends VespaResult
{
    protected $documents = [];
    protected $relevance;
    protected $source;
    protected $document_definition;
    protected $document;
    protected $id;

    public function __construct(string $result, $only_raw = false, object $child)
    {
        parent::__construct($result, $only_raw);
        //if json cannot be decoded
        if($child === null) {
            throw new \Exception("Invalid format to response child.");
        }
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
