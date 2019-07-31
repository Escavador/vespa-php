<?php

namespace Escavador\Vespa\Common;

use Escavador\Vespa\Models\Document;

class Utils
{
    public static function vespaHost()
    {
        $host = trim(config('vespa.host'));
        if(!strpos($host, 'http://') || !strpos($host, 'https://'))
            $host = 'http://'. $host;

        if (filter_var($host, FILTER_VALIDATE_URL) === FALSE)
            //TODO
            throw new \Exception('Invalid Vespa Host');

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
}
