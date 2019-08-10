<?php

namespace Escavador\Vespa\Models;


use Carbon\Carbon;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;


/**
* 
* RESTified Document Operation API: Simple REST API for operations based on document ID (get, put, remove, update,visit).
* 
* See: https://docs.vespa.ai/documentation/writing-to-vespa.html
*
*/
class VespaRESTClient extends AbstractClient
{
    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function search(array $data) : VespaResult
    {
        $searchIsGrouping = array_key_exists('yql', $data) && strpos($data['yql'], '|') !== false;

        try
        {
            $response = $this->client->post(Utils::vespaSearchEndPoint(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $data
            ]);
        }
        catch (\Exception $ex)
        {
            //TODO Custom Exception
            throw new \Exception("Error Processing Request");
        }

        if ($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();

            if($searchIsGrouping)
                return  new GroupedSearchResult($content);
            else
                return  new SearchResult($content);
        }

        //TODO Custom Exception
        throw new \Exception("Error Processing Request");
    }

    public function removeDocument($scheme)
    {
        $definition = DocumentDefinition::schemeToDocument($scheme, $this->documents);
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$definition->getUserPercified()}";

        try
        {
            $response = $this->client->delete($url);
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

    public function updateDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
        try
        {
            $response = $this->client->put($url,  [
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
            try
            {
                if($this->sendDocument($definition, $document))
                    $indexed[] = $document;
            }
            catch (\Exception $ex)//TODO Custom Exception
            {
                continue;
            }
        }

        return $indexed;
    }
}
