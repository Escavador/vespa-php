<?php

namespace Escavador\Vespa\Enum;

abstract class LogManagerOptionsEnum extends BaseEnum
{
    const INFO = 'info';
    const ERROR = 'error';
    const ERR = 'err';
    const DEBUG = 'debug';
    const WARN = 'warn';
    const WARNING = 'warning';
}
