<?php

namespace Escavador\Vespa\Interfaces;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Models\DocumentDefinition;
use Escavador\Vespa\Models\DocumentNamespace;
use Escavador\Vespa\Models\DocumentType;

abstract class VespaResult
{
    protected $json_data;
    protected $only_raw;
    protected $id;
    protected $fields;

    public function __construct(string $result, $only_raw = false)
    {
        $this->json_data = $result;
        $this->only_raw = $only_raw;

        //if json cannot be decoded
        if ($result === null) throw new \Exception("Invalid response");
    }

    public final function onlyRaw() : bool
    {
        return $this->only_raw;
    }

    public final function raw() : string
    {
        return $this->json_data;
    }

    public final function json() : string
    {
        return json_encode($this->json_data);
    }

    public final function field($key)
    {
        $fields = $this->getAttribute('fields');

        if (!$key || !$fields || !property_exists($fields, $key))
        {
            return null;
        }

        return $fields->$key;
    }

    public  final function fields() : object
    {
        return $this->fields;
    }

    public final function getAttribute($attribute)
    {
        if($this->onlyRaw())
        {
            $e = new VespaException("This response was not normalized. Please see the \"raw\" property. {$this->raw()}");
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }

        if (!$attribute || !property_exists($this, $attribute)) {
            return null;
        }

        return $this->$attribute;
    }
}
