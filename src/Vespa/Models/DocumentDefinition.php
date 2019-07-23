<?php

namespace Escavador\Vespa\Models;

class DocumentDefinition
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

    public function getModelTable()
    {
        return $this->model_table;
    }

    public function getModelClass()
    {
        return $this->model_class;
    }

    public function getDocumentNamespace()
    {
        return $this->namespace;
    }

    public function getDocumentType()
    {
        return $this->type;
    }

    public static function loadDefinition()
    {
        $namespaces = config('vespa.namespace', []);
        $documents = [];

        foreach ($namespaces as $namespace => $namespace_data)
        {
            foreach ($namespace_data["document"] as $document)
            {
                $documents[] = new DocumentDefinition($namespace, $document['type'], $document['class'], $document['table']);
            }
        }

        return $documents;
    }

    public static function hasType($document_type, $namespace = null, $definitions = null)
    {
        return DocumentDefinition::findDefinition($document_type, $namespace, $definitions) != null;
    }

    public static function hasNamespace($namespace, $definitions = null)
    {
        if($definitions == null)
            $definitions = DocumentDefinition::loadDefinition();

        foreach ($definitions as $definition)
        {
            if($definition->getDocumentNamespace() == $namespace)
            {
                return true;
            }
        }

        return false;
    }

    public static function findNamespace($namespace = null, $definitions = null)
    {
        if($definitions == null)
            $definitions = DocumentDefinition::loadDefinition();

        $filtred_definitions = [];

        foreach ($definitions as $definition)
        {
            if($namespace && $definition->getDocumentNamespace() != $namespace)
            {
                continue;
            }

            if($definition->getDocumentNamespace() == $namespace)
                $filtred_definitions[] =$definition;
        }

        return $filtred_definitions;
    }

    public static function findDefinition($document_type, $namespace = null, $definitions = null)
    {
        if($definitions == null)
            $definitions = DocumentDefinition::loadDefinition();

       foreach ($definitions as $definition)
       {
           if($namespace && $definition->getDocumentNamespace() != $namespace)
           {
               continue;
           }
           if($namespace)
           {
               if($definition->getDocumentNamespace() == $namespace && $definition->getDocumentType() == $document_type)
               {
                   return $definition;
               }
           }
           else if($definition->getDocumentType() == $document_type)
               return $definition;
       }

       return null;
    }
}
