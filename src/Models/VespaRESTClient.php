<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Common\LogManager;
use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Common\VespaExceptionSubject;
use Escavador\Vespa\Enum\LogManagerOptionsEnum;
use Escavador\Vespa\Exception\VespaFailDeleteDocumentException;
use Escavador\Vespa\Exception\VespaFailGetDocumentException;
use Escavador\Vespa\Exception\VespaFailSearchException;
use Escavador\Vespa\Exception\VespaFailSendDocumentException;
use Escavador\Vespa\Exception\VespaFailUpdateDocumentException;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Interfaces\AbstractDocument;
use Escavador\Vespa\Interfaces\VespaResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
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
    protected $max_parallel_requests;

    public function __construct(array $headers = null)
    {
        parent::__construct();
        $this->client = new Client();
        $this->max_concurrency = config('vespa.default.vespa_rest_client.max_concurrency', 6);
        $this->max_parallel_requests = intval(config('vespa.default.max_parallel_requests.feed', 1000));
        $this->logger = new LogManager();

        if ($headers) {
            $this->headers = $headers;
        }
    }

    public function search(array $data): VespaResult
    {
        try {
            $data = Utils::utf8ize($data);
            $this->logger->log("Running the search on the Vespa server: " . json_encode($data), 'debug');
            $response = $this->client->post(Utils::vespaSearchEndPoint(), [
                'headers' => $this->headers,
                'json' => $data
            ]);
        } catch (\Exception $ex) {
            $e = new VespaFailSearchException($data, $ex);
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }

        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $searchIsGrouping = strpos($content, "group:root") !== false; //TODO improve this check
            if ($searchIsGrouping) {
                return new GroupedSearchResult($content);
            } else {
                return new SearchResult($content);
            }
        }

        $e = new VespaFailSearchException($data, new \Exception($response->getBody()->getContents(), $response->getStatusCode()));
        $this->logger->log($e->getMessage(), 'error');
        VespaExceptionSubject::notifyObservers($e);
        throw $e;
    }

    public function removeDocument($scheme)
    {
        $definition = DocumentDefinition::schemeToDocument($scheme, $this->documents);
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$definition->getUserPercified()}";

        try {
            $response = $this->client->delete($url, ['headers' => $this->headers]);
        } catch (\Exception $ex) {
            $e = new VespaFailDeleteDocumentException($definition, $scheme, $ex);
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }

        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $result = new DocumentResult($content);
            if ($result->onlyRaw()) {
                return $result;
            }

            return $result->document();
        } else {
            $e = new VespaFailDeleteDocumentException($definition, $scheme, new \Exception($response->getBody()->getContents(), $response->getStatusCode()));
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function updateDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
        try {
            $response = $this->client->put($url, [
                'headers' => $this->headers,
                RequestOptions::JSON => array('fields' => $document->getVespaDocumentFields())
            ]);
        } catch (\Exception $ex) {
            $e = new VespaFailUpdateDocumentException($definition, $document, $ex);
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }

        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $content = json_decode($content);

            return $content;
        } else {
            $e = new VespaFailUpdateDocumentException($definition, $document, new \Exception($response->getBody()->getContents(), $response->getStatusCode()));
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function getDocument(string $scheme): VespaResult
    {
        $definition = DocumentDefinition::schemeToDocument($scheme, $this->documents);
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$definition->getUserPercified()}";
        try {
            $response = $this->client->get($url, ['headers' => $this->headers]);
        } catch (\Exception $ex) {
            $e = new VespaFailGetDocumentException($definition, $scheme, $ex);
            $this->logger->log($e->getMessage(), LogManagerOptionsEnum::ERROR);
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }

        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $result = new DocumentResult($content);
            if ($result->onlyRaw()) {
                return $result;
            }

            return $result->document();
        } else {
            $e = new VespaFailGetDocumentException($definition, $scheme, new \Exception($response->getBody()->getContents(), $response->getStatusCode()));
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function sendDocument(DocumentDefinition $definition, AbstractDocument $document)
    {
        $url = $this->host . "/document/v1/{$definition->getDocumentNamespace()}/{$definition->getDocumentType()}/docid/{$document->getVespaDocumentId()}";
        try {
            $response = $this->client->post($url, [
                'headers' => $this->headers,
                RequestOptions::JSON => array('fields' => $document->getVespaDocumentFields())
            ]);

            if ($response->getStatusCode() == 200) {
                return $document;
            } else {
                throw new \Exception($response->getBody()->getContents(), $response->getStatusCode());
            }
        } catch (\Exception $ex) {
            $e = new VespaFailSendDocumentException($definition, $document, $ex);
            $this->logger->log($e->getMessage(), 'error');
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }
    }

    public function sendDocuments(DocumentDefinition $definition, $documents)
    {
        $indexed = array();
        $document_type = $definition->getDocumentType();
        $document_namespace = $definition->getDocumentNamespace();

        $requests = function ($documents, $definition) use (&$document_type, &$document_namespace) {
            foreach ($documents as $document) {
                $scheme = "id:{$document_namespace}:{$document_type}::{$document->getVespaDocumentId()}";
                $url = $this->host . "/document/v1/{$document_namespace}/{$document_type}/docid/{$document->getVespaDocumentId()}";
                yield new Request('POST', $url, $this->headers, json_encode(['fields' => $document->getVespaDocumentFields()]));
            }
        };

        $documents_chunk = array_chunk($documents, $this->max_parallel_requests);
        foreach ($documents_chunk as $chunk) {
            $pool = new Pool($this->client, $requests($chunk, $definition), [
                'concurrency' => $this->max_concurrency,
                'fulfilled' => function (Response $response, $index) use (&$chunk, &$indexed, &$document_type, &$document_namespace) {
                    $document = $chunk[$index];
                    $indexed[] = $document;
                    $scheme = "id:{$document_namespace}:{$document_type}::{$document->getVespaDocumentId()}";
                    $this->logger->log("Document $scheme was indexed to Vespa", 'debug');
                },
                'rejected' => function (RequestException $reason, $index) use (&$definition, &$chunk, &$document_type, &$document_namespace) {
                    $document = $chunk[$index];
                    $e = new VespaFailSendDocumentException($definition, $document, $reason);
                    $this->logger->log($e->getMessage(), 'error');
                    VespaExceptionSubject::notifyObservers($e);
                },
            ]);

            // Initiate the transfers and create a promise
            $promise = $pool->promise();

            // Force the pool of requests to complete.
            $promise->wait();
        }

        return $indexed;
    }
}
