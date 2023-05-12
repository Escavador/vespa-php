<?php

namespace Escavador\Vespa\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as IlluminateMigrationCreator;
use Illuminate\Filesystem\Filesystem;

class MigrationCreator extends IlluminateMigrationCreator
{
    /**
     * Create a new migration creator instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param string $customStubPath
     * @return void
     */
    public function __construct(Filesystem $files, $customStubPath = null)
    {
        parent::__construct($files, $customStubPath);
    }

    /**
     * Get the migration stub file.
     *
     * @param string $table
     * @param bool $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        return $this->files->get(__DIR__ . "/stubs/blank.stub");
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param string $stub
     * @param string $table
     * @return string
     */
    protected function populateStub($stub, $table)
    {
        $stub = str_replace('DummyTable', $table, $stub);
        $stub = str_replace('DummyColumnNameStatus', config('vespa.model_columns.status', ''), $stub);
        $stub = str_replace('DummyColumnNameDate', config('vespa.model_columns.date', ''), $stub);
        $stub = str_replace('DummyColumnCommentDate', config('vespa.model_columns.comment_date', 'Date of last indexing in Vespa.'), $stub);
        $stub = str_replace('DummyColumnCommentStatus', config('vespa.model_columns.comment_status', 'State of model indexing in Vespa.'), $stub);

        return $stub;
    }

    /**
     * Get the full path to the migration.
     *
     * @param string $name
     * @param string $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }
}
