<?php

namespace Escavador\Vespa;

use Illuminate\Support\ServiceProvider;
use Escavador\Vespa\Commands\FeedCommand;
use Escavador\Vespa\Commands\VespaMigrateMakeCommand;
use Escavador\Vespa\Migrations\VespaMigrationCreator;

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
            __DIR__.'/../../config/application' => app()->basePath().'/resources/config/vendor/vespa/application',
        ], 'vespa-properties');

        $this->publishes([
            __DIR__.'/../../config/vespa.php' => app()->basePath().'/config/vespa.php',
        ], 'vespa-config');

        $this->mergeConfigFrom(__DIR__.'/../../config/vespa.php', 'vespa');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FeedCommand::class,
                VespaMigrateMakeCommand::class,
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
    }


    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('vespa.migration.creator', function ($app) {
            return new VespaMigrationCreator($app['files']);
        });
    }

}
