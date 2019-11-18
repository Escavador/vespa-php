<?php

namespace Escavador\Vespa\Common;

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
            //TODO
            $e = new \Exception('Invalid Vespa Host');
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
}
