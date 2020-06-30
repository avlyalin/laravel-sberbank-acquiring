<?php

namespace Avlyalin\SberbankAcquiring\Tests;

use Avlyalin\SberbankAcquiring\AcquiringServiceProvider;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentOperation;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->registerEloquentFactories($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [AcquiringServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * @param Application $app
     */
    private function setUpDatabase(Application $app)
    {
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/laravel/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Регистрация фабрик
     *
     * @param Application $app
     */
    private function registerEloquentFactories(Application $app)
    {
        $factory = $app->make(Factory::class);
        $factory->load(__DIR__ . '/../database/factories');
        $factory->load(__DIR__ . '/../vendor/laravel/laravel/database/factories');
    }

    protected function createUser(array $attributes = [])
    {
        $userModel = config('sberbank-acquiring.user.model');
        return factory($userModel)->create($attributes);
    }

    protected function createAcquiringPayment(array $attributes = []): AcquiringPayment
    {
        return factory(AcquiringPayment::class)->create($attributes);
    }

    protected function createSberbankPayment(array $attributes = []): SberbankPayment
    {
        return factory(SberbankPayment::class)->create($attributes);
    }

    protected function createApplePayPayment(array $attributes = []): ApplePayPayment
    {
        return factory(ApplePayPayment::class)->create($attributes);
    }

    protected function createSamsungPayPayment(array $attributes = []): SamsungPayPayment
    {
        return factory(SamsungPayPayment::class)->create($attributes);
    }

    protected function createGooglePayPayment(array $attributes = []): GooglePayPayment
    {
        return factory(GooglePayPayment::class)->create($attributes);
    }

    protected function createAcquiringPaymentOperation(array $attributes = []): AcquiringPaymentOperation
    {
        return factory(AcquiringPaymentOperation::class)->create($attributes);
    }
}
