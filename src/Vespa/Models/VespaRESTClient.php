<?php

namespace Escavador\Vespa\Models;


use Carbon\Carbon;
use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
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
        $this->logger =  new LogManager();

        if($headers)
        {
            $this->headers = $headers;
        }
    }

    public function search(array $data) : VespaResult
    {
        try
        {
            $this->logger->log("Running the search on the Vespa server: ". json_encode($data), 'debug');
            $response = $this->client->post(Utils::vespaSearchEndPoint(), [
                'headers' => $this->headers,
                'json' => $data
            ]);
        }
        catch (\Exception $ex)
        {
            //TODO Custom Exception
            $this->logger->log($ex->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($ex);
            throw $ex;
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
        $exception_message = "Error Processing Request [{$response->getBody()}]";
        $this->logger->log($exception_message, 'error');
        $e = new \Exception($exception_message, $response->getStatusCode());
        VespaExceptionSubject::notifyObservers($e);
        throw $e;
    }

    public function removeDocument($scheme)
    {
        $definition = DocumentDefinition::schemeToDocument($scheme, $this->documents);
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$definition->getUserPercified()}";

        try
        {
            $response = $this->client->delete($url, ['headers' => $this->headers]);
        } catch (\Exception $ex)
        {
            //TODO Custom Exception
            $this->logger->log($ex->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($ex);
            throw $ex;
        }

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $result = new DocumentResult($content);
            if($result->onlyRaw())
                return $result;

            return $result->document();
        }
        else
        {
            //TODO Custom Exception
            $exception_message = "Error Processing Request [{$response->getBody()}]";
            $this->logger->log($exception_message, 'error');
            $e = new \Exception($exception_message, $response->getStatusCode());
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function updateDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
        try
        {
            $response = $this->client->put($url,  [
                'headers' => $this->headers,
                RequestOptions::JSON => array('fields' => $document->getVespaDocumentFields())
            ]);

        } catch (\Exception $ex)
        {
            //TODO Custom Exception
            $this->logger->log($ex->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($ex);
            throw $ex;
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
            $exception_message = "Error Processing Request [{$response->getBody()}]";
            $this->logger->log($exception_message, 'error');
            $e = new \Exception($exception_message, $response->getStatusCode());
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function getDocument(string $scheme) : VespaResult
    {
        $definition = DocumentDefinition::schemeToDocument($scheme, $this->documents);
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$definition->getUserPercified()}";
        try
        {
            $response = $this->client->get($url, ['headers' => $this->headers]);
        } catch (\Exception $ex)
        {
            //TODO Custom Exception
            $this->logger->log($ex->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($ex);
            throw $ex;
        }

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $result = new DocumentResult($content);
            if($result->onlyRaw())
                return $result;

            return $result->document();
        }
        else
        {
            //TODO Custom Exception
            $exception_message = "Error Processing Request [{$response->getBody()}]";
            $this->logger->log($exception_message, 'error');
            $e = new \Exception($exception_message, $response->getStatusCode());
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function sendDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
        try
        {
            $response = $this->client->post($url,  [
                'headers' => $this->headers,
                RequestOptions::JSON => array('fields' => $document->getVespaDocumentFields())
            ]);

        }
        catch (\Exception $ex)
        {
            //TODO Custom Exception
            VespaExceptionSubject::notifyObservers($ex);
            throw $ex;
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
            $exception_message = "Error Processing Request [{$response->getBody()}]";
            $this->logger->log($exception_message, 'error');
            $e = new \Exception($exception_message, $response->getStatusCode());
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function sendDocuments(DocumentDefinition $definition, $documents)
    {
        $indexed = array();
        $document_type = $definition->getDocumentType();
        $document_namespace =  $definition->getDocumentNamespace();

        $requests = function ($documents, $definition) use (&$document_type, &$document_namespace)
        {
            foreach ($documents as $document)
            {
                $scheme = "id:{$document_namespace}:{$document_type}::{$document->getVespaDocumentId()}";
                $url = $this->host . "/document/v1/{$document_namespace}/{$document_type}/docid/{$document->getVespaDocumentId()}";
                yield new Request('POST', $url, $this->headers, json_encode(['fields' =>  $document->getVespaDocumentFields()]));
            }
        };

        $pool = new Pool($this->client, $requests($documents, $definition), [
            'concurrency' => $this->max_concurrency,
            'fulfilled' => function (Response $response, $index) use (&$documents, &$indexed, &$document_type, &$document_namespace)
            {
                $document = $documents[$index];
                $scheme = "id:{$document_namespace}:{$document_type}::{$document->getVespaDocumentId()}";
                $this->logger->log("Document $scheme was indexed to Vespa", 'debug');
                $indexed[] = $document;
            },
            'rejected' => function (RequestException $reason, $index) use (&$documents, &$document_type, &$document_namespace)
            {
                $e = new \Exception("[$document_type]: Document ".$documents[$index]->getVespaDocumentId().
                                            " was not indexed to Vespa. Some error has occurred. ".
                                            "[".$reason->getCode()."][".$reason->getMessage()."]");
                $this->logger->log($e->getMessage(), 'error');
                VespaExceptionSubject::notifyObservers($e);
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return $indexed;
    }
}
