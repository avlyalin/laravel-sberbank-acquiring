<?php

namespace Avlyalin\SberbankAcquiring;

use Illuminate\Support\ServiceProvider;

class AcquiringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sberbank-acquiring.php',
            'sberbank-acquiring'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/sberbank-acquiring.php' => config_path('sberbank-acquiring.php'),
        ], 'config');
    }
}
