<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;

class DocumentResult  extends VespaResult
{
    protected $pathId;
    protected $document;

    public function __construct(string $result, $only_raw = false)
    {
        parent::__construct($result, $only_raw);

        if($this->only_raw)
            return;

        try
        {
            $this->parseDocument((object) $this->json());
            $this->only_raw = false;
        }
        catch (\Exception $ex) //TOOD Custom Exception
        {
            $this->only_raw = true;
        }
    }

    public function document() : AbstractDocument
    {
        return $this->getAttribute('document');
    }

    private function parseDocument($result)
    {
        $this->id = $result->id;
        $this->pathId = $result->pathId;
        $this->fields = $result->fields;
        $this->document_definition = DocumentDefinition::schemeToDocument($result->id);
        if(!$this->document_definition || !$this->document_definition->getModelClass())
        {
            //TODO Custom Exception
            throw new \Exception("Could not find a document definition for this Vespa response.");
        }
        $model_class = $this->document_definition->getModelClass();
        $this->document = $model_class::instanceByVespaChildResponse($this);
    }
}
