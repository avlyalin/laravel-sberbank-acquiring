<?php

namespace Avlyalin\SberbankAcquiring\Providers;

use Avlyalin\SberbankAcquiring\Client\ApiClient;
use Avlyalin\SberbankAcquiring\Client\ApiClientInterface;
use Avlyalin\SberbankAcquiring\Client\Curl\Curl;
use Avlyalin\SberbankAcquiring\Client\Curl\CurlInterface;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class AcquiringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sberbank-acquiring.php',
            'sberbank-acquiring'
        );

        $this->app->register(EventServiceProvider::class);

        $this->registerBindings();

        $this->registerEloquentFactories();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/sberbank-acquiring.php' => config_path('sberbank-acquiring.php'),
        ], 'config');
    }

    /**
     * Регистрация фабрик
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function registerEloquentFactories()
    {
        $factory = $this->app->make(Factory::class);
        $factory->load(base_path('vendor/avlyalin/laravel-sberbank-acquiring/database/factories'));
    }

    /**
     * Регистрация биндингов
     */
    private function registerBindings()
    {
        $this->app->bind(CurlInterface::class, Curl::class);
        $this->app->bind(ApiClientInterface::class, ApiClient::class);
    }
}
