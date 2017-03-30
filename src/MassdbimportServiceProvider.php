<?php

namespace Weblid\Massdbimport;

use Illuminate\Support\ServiceProvider;

class MassdbimportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Massdbimport::class, function ($app) {
            return new Massdbimport();
        });
    }
}
