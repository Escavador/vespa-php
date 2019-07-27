<?php

namespace Escavador\Vespa\Common;

use Escavador\Vespa\Models\Document;

class Utils
{
    public static function vespaHost()
    {
        $host = trim(config('vespa.host'));
        if(strpos($host, 'http://') != 0 || strpos($host, 'https://') != 0)
            $host = 'http://'. $host;

        if (filter_var($host, FILTER_VALIDATE_URL) === FALSE)
            throw new \Exception('');

        return $host;
    }

    /**
     * Returns the /search/ endpoint of vespa.
     *
     * See: https://docs.vespa.ai/documentation/search-api.html
     *
     * @return string
     */
    public function searchUrl()
    {
        return Utils::vespaHost().'/search/';
    }
}
