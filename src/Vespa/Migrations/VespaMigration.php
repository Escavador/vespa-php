 <?php

namespace Escavador\Vespa\Migrations;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;

class VespaServiceProvider extends MigrateMakeCommand
{

	/**
     * The console command signature.
     *
     * @var string
     */
	protected $signature = 'vespa:migration {name : The name of the migration}
        {--table= : The table to migrate}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

 	/**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new basic vespa migration file';


     /**
     * Get the migration stub file.
     *
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        return $this->files->get($this->stubPath()."/blank.stub");
    }
}
