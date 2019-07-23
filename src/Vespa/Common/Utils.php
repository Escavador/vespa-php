<?php

namespace Escavador\Vespa\Common;

use Escavador\Vespa\Models\Document;

class Utils
{
    /**
     * Returns the /search/ endpoint of vespa.
     *
     * See: https://docs.vespa.ai/documentation/search-api.html
     *
     * @return string
     */
    public function searchUrl()
    {
        //TODO
        return null;
    }


    public static function loadDocuments()
    {
        $namespaces_data = config('vespa.namespace', []);
        dd($namespaces_data);

        $documents = [];
        
        foreach ($namespaces as $namespace => $values)
        {
            foreach ($values["document"] as $document)
            {
                $documents[] = new Document($key, $document['type'], $document['class'], $document['table']);
            }
        }

        return $documents;
    }
}
