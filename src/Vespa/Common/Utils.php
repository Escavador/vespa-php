<?php

namespace Escavador\Vespa\Common;

use Escavador\Vespa\Exception\VespaInvalidHostException;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Models\Document;
use Escavador\Vespa\Models\VespaRESTClient;

class Utils
{
    public static function vespaHost()
    {
        $host = trim(config('vespa.host'));
        if(strpos($host, 'http://') !== 0 && strpos($host, 'https://') !== 0)
            $host = 'http://'. $host;

        if (filter_var($host, FILTER_VALIDATE_URL) === FALSE)
        {
            $e = new VespaInvalidHostException();
            VespaExceptionSubject::notifyObservers($e);
            throw $e;
        }

        return $host;
    }

    /**
     * Returns the /search/ endpoint of vespa.
     *
     * See: https://docs.vespa.ai/documentation/search-api.html
     *
     * @return string
     */
    public static function vespaSearchEndPoint()
    {
        return Utils::vespaHost().'/search/';
    }

    public static function defaultVespaClient() : AbstractClient
    {
        $default_client = config('vespa.default.client', VespaRESTClient::class);
        return new $default_client;
    }

    public static function removeQuotes(string $text) : string
    {
        $text = preg_replace('/^"(.*)"$/i', '${1}', $text);
        return  addslashes(preg_replace("/^'(.*)'$/i", '${1}', $text));
    }

    public static function removeExtraSpace(string $text) : string
    {
        return trim(preg_replace("/\s+/", '${1} ', $text));
    }

    public static function removeSpecialCharacters(string $text) : string
    {
        return preg_replace('/[°ºª#$%^&*()+=\-\[\]\';,.\/{}|":<>?~\\\\]/', '${1} ', $text);
    }
}
