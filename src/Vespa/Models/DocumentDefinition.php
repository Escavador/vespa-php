<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractDocument;

class DocumentDefinition
{
    /**
     * This dict specifies the meaning of the keys of the key/value_pairs of a Document
     * when constructing the Document URI. Refer: https://docs.vespa.ai/documentation/documents.html#id-scheme
     */
    public const KEY_VALUE_PAIRS_LOOKUP = ['n'=> 'number', 'g'=> 'group'];

    protected $namespace;
    protected $model_class;
    protected $model_table;
    protected $type;
    protected $key_values;
    protected $user_pecified;

    public function __construct($namespace, $type, $model_class, $model_table, $key_values = null, $user_pecified = null)
    {
        $this->namespace = $namespace;
        $this->type = $type;
        $this->model_class = $model_class;
        $this->model_table = $model_table;
        $this->key_values = $key_values;
        $this->user_pecified = $user_pecified;
    }

    public function getUserPercified()
    {
        return $this->user_pecified;
    }

    public function getKeyValues()
    {
        return $this->key_values;
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

    public static function findDefinitionByClass(string $model_class, $namespace = null, $definitions = null)
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
                if($definition->getDocumentNamespace() == $namespace && $definition->getModelClass() == $model_class)
                {
                    return $definition;
                }
            }
            else if($definition->getModelClass() == $model_class)
                return $definition;
        }

        return null;
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

    public static function findAllTypes($definitions = null)
    {
        if($definitions == null)
            $definitions = DocumentDefinition::loadDefinition();

        $all_type = [];
        foreach ($definitions as $definition)
        {
            if (!in_array($definition->getDocumentType(), $all_type)) {
                $all_type[] = $definition->getDocumentType();
            }
        }

        return $all_type;
    }

    /**
     * Parses the document id scheme.
     * i.e. $scheme ='id:music:music:g=mymusicsite.com:Michael-Jackson-Bad'
     * See: https://docs.vespa.ai/documentation/documents.html#id-scheme
     */
    public static function schemeToDocument(string $scheme, $definitions = null) : DocumentDefinition
    {
        try
        {
            list($id, $namespace, $document_type, $key_values, $user_pecified) = explode(':', $scheme);
            $key_value_pairs = null;

            if(strlen($key_values) > 0)
            {
                $key_value_pairs = [];
                foreach (explode(',', $key_values) as $k_value)
                {
                    list($key, $value) = explode("=", $k_value);
                    $key_value_pairs[$key] = $value;
                }
            }
            $document_definition = DocumentDefinition::findDefinition($document_type, $namespace, $definitions);
            $document_definition->key_values = $key_values;
            $document_definition->user_pecified = $user_pecified;
            return $document_definition;
        }
        catch(\Exception $ex)
        {
            //TODO Custom Exeception
            throw $ex;
        }
    }

    /**
     * Computes the document http uri access location.
     * See: https://docs.vespa.ai/documentation/document-api.html#document-format
     */
    public static function documentToScheme(DocumentDefinition $document) : string
    {
        try
        {
            if(strlen($key_values) > 0)
            {
                $store_mode = 'docid';
            }
            else
            {
                $store_mode = '/';
                foreach ($document->key_values as $key => $value)
                {
                    $store_mode.= KEY_VALUE_PAIRS_LOOKUP[$key].'/'.$value;
                }
            }

            return Utils::vespaHost().'/document/v1/'.$document->getDocumentNamespace().'/'.$document->getDocumentType().'/'.$store_mode.'/'.$document->user_pecified;
        }
        catch(\Exception $ex)
        {
            //TODO Custom Exeception
            throw $ex;
        }
        return '';
    }
}
