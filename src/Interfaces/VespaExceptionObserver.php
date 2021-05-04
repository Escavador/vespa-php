<?php

namespace Escavador\Vespa\Interfaces;

abstract class VespaExceptionObserver
{
    abstract public static function notify($expection);
}
