<?php

namespace Escavador\Vespa\Commands;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Support\Str;

class VespaMigrateMakeCommand extends MigrateMakeCommand
{

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'vespa:migration {name : The name of the migration}
        {table : The table to migrate}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new basic vespa migration file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = Str::snake(trim($this->input->getArgument('name')));

        $table = $this->input->getArgument('table');

        $this->writeMigration($name, $table, false);

        $this->composer->dumpAutoloads();
    }

}
