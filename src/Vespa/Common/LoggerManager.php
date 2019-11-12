<?php

namespace Escavador\Vespa\Common;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerManager
{
    protected $channel;

    public function __construct()
    {
        $this->channel = config('vespa.default.log.channel', 'daily');
    }

    public function log(string $message, string $type = 'debug')
    {
        switch ($type)
        {
            case 'error':
            case 'err':
                Log::channel($this->channel)->error($message);
                break;
            case 'info':
                Log::channel($this->channel)->info($message);
                break;
            case 'warn':
            case 'warning':
                Log::channel($this->channel)->warn($message);
                break;
            default:
                Log::channel($this->channel)->debug($message);
        }
    }
}
