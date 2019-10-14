<?php

namespace Escavador\Vespa\Models;


use Carbon\Carbon;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
    protected $max_concurrency;

    public function __construct(array $headers = null)
    {
        parent::__construct();
        $this->client = new Client();
        $this->max_concurrency = config('vespa.default.vespa_rest_client.max_concurrency', 6);
    }

    public function search(array $data) : VespaResult
    {
        try
        {
            $response = $this->client->post(Utils::vespaSearchEndPoint(), [
                'headers' => $this->headers,
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
            $searchIsGrouping = strpos($content, "group:root") !== false; //TODO improve this check
            if($searchIsGrouping)
                return new GroupedSearchResult($content);
            else
                return new SearchResult($content);
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
            $response = $this->client->delete($url, $this->headers);
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
            $response = $this->client->get($url, $this->headers);
        } catch (\Exception $ex)
        {
            //TODO Custom Exception
            throw new \Exception(get_class($this).": Error Processing Request", $ex->getCode(), $ex);
        }

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $result = new DocumentResult($content);
            if($result->onlyRaw())
                //TODO Custom Exception
                throw new \Exception(get_class($this).": Error Processing Response. Only raw data is available.", $ex->getCode(), $ex);

            return $result->document();
        }
        else
        {
            //TODO Custom Exception
            throw new Exception(get_class($this).": Error Processing Response [{$response->getStatusCode()}]", $ex->getCode(), $ex);
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
            throw new \Exception(get_class($this).": Error Processing Request", $ex->getCode(), $ex);
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
            throw new Exception(get_class($this).": Error Processing Response [{$response->getStatusCode()}]", $ex->getCode(), $ex);
        }
    }

    public function sendDocuments(DocumentDefinition $definition, $documents)
    {
        $indexed = array();

        $requests = function ($documents, $definition) {
            foreach ($documents as $document)
            {
                $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
                yield new Request('POST', $url, $this->headers, json_encode(['fields' =>  $document->getVespaDocumentFields()]));
            }
        };

        $pool = new Pool($this->client, $requests($documents, $definition), [
            'concurrency' => $this->max_concurrency,
            'fulfilled' => function (Response $response, $index) use (&$documents, &$indexed){
                $indexed[] = $documents[$index];
            },
            'rejected' => function (RequestException $reason, $index) {
                //TODO log this
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return $indexed;
    }
}
