<?php


namespace Escavador\Vespa\Interfaces;


abstract class VespaExceptionObserver
{
    public abstract static function notify($expection);
}
