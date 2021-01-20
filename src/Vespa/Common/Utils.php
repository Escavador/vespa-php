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

    public static function removeSpecialCharacters(string $text, $replacement = '') : string
    {
        return trim(preg_replace('/[\x00-\x1F\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\xBF\xC5\xC6\xD0\xD7\xD8\xDE\xDF\xE5\xE6\xF0\xF7\xF8\x{00FE}-\x{FFFF}]/u', $replacement, $text));
    }

    /**
     * Use it for json_encode some corrupt UTF-8 chars
     * useful for = malformed utf-8 characters possibly incorrectly encoded by json_encode
     * @param $mixed
     * @return array|bool|false|string|string[]|null
     */
    public static function utf8ize($mixed)
    {
        if(is_array($mixed))
        {
            foreach ($mixed as $key => $value)
            {
                $mixed[$key] = Utils::utf8ize($value);
            }
        }
        elseif(is_string($mixed))
        {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }

        return $mixed;
    }
}
