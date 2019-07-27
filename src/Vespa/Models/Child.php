<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;

class Child
{
    protected $documents = [];
    protected $relevance;
    protected $source;
    protected $document_definition;
    protected $document;
    protected $id;
    protected $fields;

    public function __construct(object $child)
    {
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

    public function field($key)
    {
        if (!$key || !property_exists($this->fields, $key)) {
            return null;
        }

        return $this->fields->$key;
    }

    public function fields() : object
    {
        return $this->fields;
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
