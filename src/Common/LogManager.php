<?php

namespace Escavador\Vespa\Common;

use Escavador\Vespa\Enums\LogManagerOptionsEnum;
use Illuminate\Support\Facades\Log;

class LogManager
{
    protected $channel;

    public function __construct()
    {
        $this->channel = config('vespa.default.log.channel', 'daily');
    }

    public function log(string $message, string $type = LogManagerOptionsEnum::DEBUG)
    {
        switch ($type) {
            case LogManagerOptionsEnum::ERROR:
            case LogManagerOptionsEnum::ERR:
                Log::channel($this->channel)->error($message);
                break;
            case LogManagerOptionsEnum::INFO:
                Log::channel($this->channel)->info($message);
                break;
            case LogManagerOptionsEnum::WARN:
            case LogManagerOptionsEnum::WARNING:
                Log::channel($this->channel)->warn($message);
                break;
            default:
                Log::channel($this->channel)->debug($message);
        }
    }
}
