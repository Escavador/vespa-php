<?php

namespace Escavador\Vespa\Models;


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

	protected $host;
	protected $client;

	public function __construct($host)
    {
    	$this->host = $host;
    	$this->client = new Client();
    } 

    public function sendDocument(AbstractDocument $document)
	{
        $url = $this->host . "/document/v1/{$document->getVespaNamespace()}/{$document->getVespaDocumentType()}/docid/{$document->getVespaDocumentId()}";

        try
        {
            $response = $this->client->post($url,  [
                RequestOptions::JSON => array('fields' => $document->getVespaDocumentFields())
            ]);

        } catch (\Exception $ex)
        {
            //TODO Custom Exception
    		throw new Exception("Error Processing Request");
        }

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $content = json_decode($content);

            return $document;
        }
        else
        {
            //TODO Custom Exception
    		throw new Exception("Error Processing Request", $response->getStatusCode());
        }
	}

	public function sendDocuments(array $documents)
	{
        $indexed = array();
		foreach ($documents as $document)
		{
			$this->sendDocument($document);

            $indexed[] = $document;
		}

		return $indexed;
	}
}
