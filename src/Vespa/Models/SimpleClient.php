<?php

namespace Escavador\Vespa\Models;


use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Interfaces\AbstractDocument;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;


/**
* 
* RESTified Document Operation API: Simple REST API for operations based on document ID (get, put, remove, update,visit).
* 
* See: https://docs.vespa.ai/documentation/writing-to-vespa.html
*
*/
class SimpleClient extends AbstractClient
{
    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function search(string $term, $document_type = null, $options = null)
    {
        $result = $this->searchRaw($term, $document_type, $options);

        return new SearchResult($result);
    }

    public function searchRaw(string $term, $document_type = null, $options = null)
    {
        try
        {
            $payload = [
                'query' => $term,
            ];

            if($document_type)
                $payload['model'] = ['restrict' => $document_type];

            if(!$options)
            {
                $payload['yql'] = "select * from SOURCES * where default CONTAINS '$term';";
            }

            $data = json_encode([ 'yql' => "select * from SOURCES * where default CONTAINS '$term'"]);

            $response = $this->client->request('POST', Utils::vespaSearchEndPoint(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload
            ]);

            if($response->getStatusCode() == 200)
            {
                $content = $response->getBody()->getContents();

                return $content;
            }
        } catch (\Exception $ex)
        {
            dd($ex->getMessage());
            //TODO Custom Exception
            throw new \Exception("Error Processing Request");
        }
    }

    public function removeDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        throw new \Exception('Not implemented yet');
    }

    public function updateDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        throw new \Exception('Not implemented yet');
    }

    public function getDocument(string $scheme) : AbstractDocument
    {
        $definition = DocumentDefinition::schemeToDocument($scheme, $this->documents);
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$definition->getUserPercified()}";
        try
        {
            $response = $this->client->get($url);
        } catch (\Exception $ex)
        {
            //TODO Custom Exception
            throw new \Exception("Error Processing Request");
        }

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $result = new DocumentResult($content);
            if($result->onlyRaw())
                //TODO Custom Exception
                new Exception("Error Processing Request", $response->getStatusCode());

            return $result->document();
        }
        else
        {
            //TODO Custom Exception
            throw new Exception("Error Processing Request", $response->getStatusCode());
        }
    }

    public function getDocuments(DocumentDefinition $definition, AbstractDocument $document)
    {
        throw new \Exception('Not implemented yet');
    }

    public function sendDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
        try
        {
            $response = $this->client->post($url,  [
                RequestOptions::JSON => array('fields' => $document->getVespaDocumentFields())
            ]);

        } catch (\Exception $ex)
        {
            //TODO Custom Exception
            throw new \Exception("Error Processing Request");
        }

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $content = json_decode($content);

            return $content;
        }
        else
        {
            //TODO Custom Exception
            throw new Exception("Error Processing Request", $response->getStatusCode());
        }
    }

    public function sendDocuments(DocumentDefinition $definition, $documents)
    {
        $indexed = array();
        foreach ($documents as $document)
        {
            $this->sendDocument($definition, $document);

            $indexed[] = $document;
        }

        return $indexed;
    }
}
