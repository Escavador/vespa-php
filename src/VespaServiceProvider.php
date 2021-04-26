<?php

namespace Escavador\Vespa;

use Escavador\Vespa\Commands\FeedCommand;
use Escavador\Vespa\Commands\MigrateMakeCommand;
use Escavador\Vespa\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider;

class VespaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/application' => app()->basePath() . '/resources/config/vendor/vespa/application',
        ], 'vespa-properties');

        $this->publishes([
            __DIR__ . '/../config/vespa.php' => app()->basePath() . '/config/vespa.php',
        ], 'vespa-config');

        $this->mergeConfigFrom(__DIR__ . '/../config/vespa.php', 'vespa');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FeedCommand::class,
                MigrateMakeCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCreator();
        $this->registerMigrateMakeCommand();
    }


    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('vespa.migration.creator', function ($app) {
            return new MigrationCreator($app['files'], null);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('vespa.command.migrate.make', function ($app) {
            $creator = $app['vespa.migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'vespa.migration.creator', 'vespa.command.migrate.make'
        ];
    }
}
