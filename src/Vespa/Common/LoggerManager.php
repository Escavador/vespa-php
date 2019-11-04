<?php

namespace Escavador\Vespa\Common;

use Carbon\Carbon;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerManager
{
    public function __construct($name = 'vespa')
    {
        $sufix = Carbon::now()->format('Y-m-d');
        $log_path = storage_path("logs/vespa-$sufix.log");
        $handler = new StreamHandler($log_path);
        $handler->setFormatter(new LineFormatter(null, null, true, true));
        $this->logger = new Logger($name);
        $this->logger->pushHandler($handler);
    }

    public function log(string $message, string $type = 'debug')
    {
        switch ($type)
        {
            case 'error':
                $this->logger->error($message);
                break;
            case 'info':
                $this->logger->info($message);
                break;
            case 'warn':
            case 'warning':
                $this->logger->warn($message);
                break;
            default:
                $this->logger->debug($message);
        }
    }
}
