<?php

namespace Escavador\Vespa\Migrations;

use Illuminate\Database\Migrations\MigrationCreator;
use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class VespaMigrationCreator extends  MigrationCreator
{

    /**
     * Get the migration stub file.
     *
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        dd(__DIR__."/stubs/blank.stub");
        return $this->files->get(__DIR__."/stubs/blank.stub");
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  string  $table
     * @return string
     */
    protected function populateStub($name, $stub, $table)
    {
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);
        $stub = str_replace('DummyTable', $table, $stub);
        $stub = str_replace('DummyColumnNameStatus', config('vespa.model_columns.status', ''), $stub);
        $stub = str_replace('DummyColumnNameDate', config('vespa.model_columns.date', ''), $stub);

        return $stub;
    }
}
