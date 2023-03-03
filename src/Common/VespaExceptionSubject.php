<?php

namespace Escavador\Vespa\Common;

use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Interfaces\VespaExceptionObserver;

final class VespaExceptionSubject
{
    final public static function notifyObservers(VespaException $expection)
    {
        $observers = config('vespa.observers.exceptions', []);

        foreach ($observers as $observer) {
            if (new $observer() instanceof VespaExceptionObserver) {
                $observer::notify($expection);
            }
        }
    }

    public function __construct()
    {
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }
}
