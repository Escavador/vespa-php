<?php

namespace Escavador\Vespa;

use Illuminate\Support\ServiceProvider;
use Escavador\Vespa\Commands\Feeder;

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
            __DIR__.'/../../config/src/main/application' => app()->basePath().'/resources/config/vendor/vespa/src/main/application',
        ], 'vespa-properties');

        $this->publishes([
            __DIR__.'/../../config/vespa.php' => app()->basePath().'/config/vespa.php',
        ], 'vespa-config');

        $this->mergeConfigFrom(__DIR__.'/../../config/vespa.php', 'vespa');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Feeder::class,
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
        //
    }
}
