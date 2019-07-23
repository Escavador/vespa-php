<?php

namespace Escavador\Vespa\Models;

class Document
{
	protected $namespace;
    protected $model_class;
    protected $model_table;
    protected $type;

    public function __construct($namespace, $type, $model_class, $model_table)
    {
    	$this->namespace = $namespace;
        $this->type = $type;
        $this->model_class = $model_class;
        $this->model_table = $model_table;
    }


    public static function loadDefinition()
    {
        $namespaces_data = config('vespa.namespace', []);
        dd($namespaces_data);

        foreach ($namespaces as $namespace => $values)
        {
            $documents = [];
            foreach ($values["document"] as $document)
            {
                $documents[] = new Document($key, $document['type'], $document['class'], $document['table']);
            }
        }

        return $namespaces;
    }

    public final function getNamespace(string $namespace)
    {

    }

    public final function getDocumentTypes(DocumentNamespace $namespace)
    {

    }

    public final function getDocumentType(DocumentNamespace $namespace, string $documentType)
    {

    }
}
