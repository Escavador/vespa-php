<?php

namespace Escavador\Vespa;

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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vespa-php');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/vespa-php'),
        ]);

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations')
        ], 'migrations');
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
