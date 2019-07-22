<?php

namespace Escavador\Vespa\Models;


use Escavador\Vespa\Interfaces\AbstractClient;
use GuzzleHttp\Client;


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
		
		$url = $this->host . '/document/v1/';

		$response = $client->request('POST', $url."/{$document->getVespaNamespace()}/{$document->getVespaDocumentType()}/docid/{$document->getVespaDocumentId()}" , [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'fields' => $document->getVespaDocumentFields()
        ]);

        if($response->getStatusCode() == 200)
        {
            $content = $response->getBody()->getContents();
            $content = json_decode($content);
        }
        else
        {
    		throw new Exception("Error Processing Request", $response->getStatusCode());
        }
	}

	public function sendDocuments(array AbstractDocument $documents)
	{
		
		$url = $this->host . '/document/v1/';

		foreach ($documents as $document)
		{
			$this->sendDocument($document);
		}
	}
}