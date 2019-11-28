<?php


namespace Escavador\Vespa\Common;


use Escavador\Vespa\Exception\VespaException;
use Escavador\Vespa\Interfaces\VespaExceptionObserver;

final class VespaExceptionSubject
{
    public final static function notifyObservers(VespaException $expection)
    {
        $observers = config('vespa.observers.exceptions', []);

        foreach ($observers as $observer)
        {
            if(new $observer() instanceof VespaExceptionObserver)
                $observer::notify($expection);
        }
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
