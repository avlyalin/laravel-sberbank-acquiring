<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Clients;

use Avlyalin\SberbankAcquiring\Client\ApiClient;
use Avlyalin\SberbankAcquiring\Client\ApiClientInterface;
use Avlyalin\SberbankAcquiring\Client\Client;
use Avlyalin\SberbankAcquiring\Client\HttpClientInterface;
use Avlyalin\SberbankAcquiring\Client\SberbankResponse;
use Avlyalin\SberbankAcquiring\Exceptions\ResponseProcessingException;
use Avlyalin\SberbankAcquiring\Factories\PaymentsFactory;
use Avlyalin\SberbankAcquiring\Helpers\Currency;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentOperationType;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;
use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;

class ClientTest extends TestCase
{
    use HasConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setAuthParams();
    }

    /**
     * @test
     */
    public function it_sets_api_clients_base_uri()
    {
        $baseUri = 'https://pay-base-uri.test';
        Config::set('sberbank-acquiring.base_uri', $baseUri);

        $apiClient = \Mockery::mock(ApiClient::class);
        $apiClient->shouldReceive('setBaseUri')->with($baseUri)->atLeast()->once();
        $this->app->instance(ApiClientInterface::class, $apiClient);

        $this->app->make(Client::class);
    }

    /**
     * @test
     * @dataProvider auth_params_data_provider
     */
    public function register_method_uses_auth_params_from_config($authParams)
    {
        Config::set('sberbank-acquiring.auth', $authParams);

        $this->mockApiClient(
            'register',
            function ($requestAmount, $requestReturnUrl, $requestParams) use ($authParams) {
                $this->assertEquals(array_intersect($authParams, $requestParams), $authParams);
                return true;
            },
            ['orderId' => '123', 'formUrl' => 'http://pay-form-url']
        );

        $client = $this->app->make(Client::class);
        $client->register(100, ['returnUrl' => 'http://return-url.test']);
    }

    /**
     * @test
     */
    public function register_method_can_use_config_params()
    {
        Config::set('sberbank-acquiring.params.return_url', 'http://return-url');
        Config::set('sberbank-acquiring.params.fail_url', 'http://fail-url');

        $this->mockApiClient(
            'register',
            function ($requestAmount, $requestReturnUrl, $requestParams) {
                $this->assertEquals('http://return-url', $requestReturnUrl);
                $this->assertEquals('http://fail-url', $requestParams['failUrl']);
                return true;
            },
            ['orderId' => '123', 'formUrl' => 'http://pay-form-url']
        );

        $client = $this->app->make(Client::class);
        $client->register(100);
    }

    /**
     * @test
     */
    public function register_method_omits_config_params_when_they_present_in_args()
    {
        Config::set('sberbank-acquiring.params.return_url', 'http://bad-return-url');
        Config::set('sberbank-acquiring.params.fail_url', 'http://bad-fail-url');

        $this->mockApiClient(
            'register',
            function ($requestAmount, $requestReturnUrl, $requestParams) {
                $this->assertEquals('http://return-url', $requestReturnUrl);
                $this->assertEquals('http://fail-url', $requestParams['failUrl']);
                return true;
            },
            ['orderId' => '123', 'formUrl' => 'http://pay-form-url']
        );

        $client = $this->app->make(Client::class);
        $client->register(100, ['returnUrl' => 'http://return-url', 'failUrl' => 'http://fail-url']);
    }

    /**
     * @test
     * @dataProvider register_method_data_provider
     */
    public function register_method_saves_payments_to_db_and_returns_response(
        $amount,
        $returnUrl,
        $params,
        $authParams,
        $apiResponse,
        $operationStatusId
    ) {
        $this->setAuthParams($authParams);
        $expectedParams = array_merge($authParams, $params);

        $this->mockApiClient(
            'register',
            function ($requestAmount, $requestReturnUrl, $requestParams) use ($amount, $returnUrl, $expectedParams) {
                $this->assertEquals($amount, $requestAmount);
                $this->assertEquals($returnUrl, $requestReturnUrl);
                $this->assertEquals($expectedParams, $requestParams);
                return true;
            },
            $apiResponse
        );

        $user = $this->createUser();
        $this->actingAs($user);

        $client = $this->app->make(Client::class);
        $requestParams = array_merge(['returnUrl' => $returnUrl], $params);
        $acquiringPayment = $client->register($amount, $requestParams);
        $sberbankPayment = $acquiringPayment->payment;
        $operations = $acquiringPayment->operations;

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertInstanceOf(SberbankPayment::class, $sberbankPayment);
        $this->assertInstanceOf(Collection::class, $operations);
        $this->assertEquals(1, $operations->count());

        $this->assertDatabaseHas($this->getTableName('payments'), [
            'bank_order_id' => $apiResponse['orderId'] ?? null,
            'status_id' => $operationStatusId,
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
        ]);
        $this->assertDatabaseHas($this->getTableName('sberbank_payments'), [
            'order_number' => $params['orderNumber'],
            'amount' => $amount,
            'currency' => $params['currency'],
            'return_url' => $returnUrl,
            'fail_url' => $params['failUrl'],
            'description' => $params['description'],
            'client_id' => $params['clientId'],
            'language' => $params['language'],
            'page_view' => $params['pageView'],
            'json_params' => json_encode($params['jsonParams']),
            'session_timeout_secs' => $params['sessionTimeoutSecs'],
            'expiration_date' => $params['expirationDate'],
            'features' => $params['features'],
            'bank_form_url' => $apiResponse['formUrl'] ?? null,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::REGISTER,
            'request_json' => json_encode(array_merge(['amount' => $amount], $requestParams)),
            'response_json' => json_encode($apiResponse),
        ]);
    }

    /**
     * @test
     */
    public function register_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage(
            'Error updating SberbankPayment. Error updating AcquiringPayment. Error updating AcquiringPaymentOperation. Response: {"orderId":"123","formUrl":"http:\/\/pay-form-url"}'
        );

        $user = $this->createUser();
        $this->actingAs($user);

        $acquiringPayment = $this->mockAcquiringPayment('update', false);
        $sberbankPayment = $this->mockSberbankPayment('update', false);
        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createAcquiringPayment')->andReturn($acquiringPayment);
        $factory->shouldReceive('createSberbankPayment')->andReturn($sberbankPayment);
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('register', function () {
            return true;
        },
            ['orderId' => '123', 'formUrl' => 'http://pay-form-url']);

        $client = $this->app->make(Client::class);

        $amount = 100;
        $returnUrl = 'http://return-url';
        $failUrl = 'http://fail-url';
        $client->register($amount, ['returnUrl' => $returnUrl, 'failUrl' => $failUrl]);
    }

    /**
     * @test
     * @dataProvider register_method_data_provider
     */
    public function register_pre_auth_method_saves_payments_to_db_and_returns_response(
        $amount,
        $returnUrl,
        $params,
        $authParams,
        $apiResponse,
        $operationStatusId
    ) {
        $this->setAuthParams($authParams);
        $expectedParams = array_merge($authParams, $params);

        $this->mockApiClient(
            'register',
            function ($requestAmount, $requestReturnUrl, $requestParams) use ($amount, $returnUrl, $expectedParams) {
                $this->assertEquals($amount, $requestAmount);
                $this->assertEquals($returnUrl, $requestReturnUrl);
                $this->assertEquals($expectedParams, $requestParams);
                return true;
            },
            $apiResponse
        );

        $user = $this->createUser();
        $this->actingAs($user);

        $client = $this->app->make(Client::class);
        $requestParams = array_merge(['returnUrl' => $returnUrl], $params);
        $acquiringPayment = $client->registerPreAuth($amount, $requestParams);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertInstanceOf(SberbankPayment::class, $acquiringPayment->payment);
        $this->assertInstanceOf(Collection::class, $acquiringPayment->operations);
        $this->assertEquals(1, $acquiringPayment->operations->count());

        $this->assertDatabaseHas($this->getTableName('payments'), [
            'bank_order_id' => $apiResponse['orderId'] ?? null,
            'status_id' => $operationStatusId,
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
        ]);
        $this->assertDatabaseHas($this->getTableName('sberbank_payments'), [
            'order_number' => $params['orderNumber'],
            'amount' => $amount,
            'currency' => $params['currency'],
            'return_url' => $returnUrl,
            'fail_url' => $params['failUrl'],
            'description' => $params['description'],
            'client_id' => $params['clientId'],
            'language' => $params['language'],
            'page_view' => $params['pageView'],
            'json_params' => json_encode($params['jsonParams']),
            'session_timeout_secs' => $params['sessionTimeoutSecs'],
            'expiration_date' => $params['expirationDate'],
            'features' => $params['features'],
            'bank_form_url' => $apiResponse['formUrl'] ?? null,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::REGISTER_PRE_AUTH,
            'request_json' => json_encode(array_merge(['amount' => $amount], $requestParams)),
            'response_json' => json_encode($apiResponse),
        ]);
    }

    /**
     * @test
     * @dataProvider auth_params_data_provider
     */
    public function deposit_method_uses_auth_params_from_config(array $authParams)
    {
        Config::set('sberbank-acquiring.auth', $authParams);

        $this->mockApiClient(
            'deposit',
            function ($paymentId, $requestAmount, $requestParams) use ($authParams) {
                $this->assertEquals($authParams, array_intersect($authParams, $requestParams));
                return true;
            },
            ['errorCode' => 0]
        );

        $acquiringPayment = $this->createAcquiringPayment();

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->deposit($acquiringPayment->id, 5000);
    }

    /**
     * @test
     */
    public function deposit_method_throws_exception_when_gets_non_existing_payment_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->mockApiClient('deposit', function () {
            return true;
        }, ['errorCode' => 0]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->deposit(99, 5000);
    }

    /**
     * @test
     */
    public function deposit_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage("Error updating AcquiringPaymentOperation. Response: {\"errorCode\":0}");

        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('deposit', function () {
            return true;
        }, ['errorCode' => 0]);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $this->createAcquiringPayment();
        $client->deposit($acquiringPayment->id, 5000);
    }

    /**
     * @test
     * @dataProvider deposit_method_data_provider
     */
    public function deposit_method_saves_operation_to_db_and_returns_response(
        int $amount,
        array $params,
        array $authParams,
        string $method,
        array $headers,
        array $response,
        int $paymentStatusId
    ) {
        $this->setAuthParams($authParams);
        $expectedParams = array_merge($authParams, $params);

        $acquiringPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::REGISTERED]);

        $this->mockApiClient('deposit', function (
            $requestPaymentId,
            $requestAmount,
            $requestParams,
            $requestMethod,
            $requestHeaders
        ) use (
            $amount,
            $method,
            $expectedParams,
            $headers,
            $acquiringPayment
        ) {
            $this->assertEquals($acquiringPayment->bank_order_id, $requestPaymentId);
            $this->assertEquals($amount, $requestAmount);
            $this->assertEquals($expectedParams, $requestParams);
            $this->assertEquals($method, $requestMethod);
            $this->assertEquals($headers, $requestHeaders);
            return true;
        }, $response);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->deposit($acquiringPayment->id, $amount, $params, $method, $headers);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'status_id' => $paymentStatusId,
            'bank_order_id' => $acquiringPayment->bank_order_id,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::DEPOSIT,
            'request_json' => json_encode(array_merge([
                'orderId' => $acquiringPayment->bank_order_id,
                'amount' => $amount,
            ], $params)),
            'response_json' => json_encode($response),
        ]);
    }

    /**
     * @test
     * @dataProvider auth_params_data_provider
     */
    public function reverse_method_uses_auth_params_from_config(array $authParams)
    {
        Config::set('sberbank-acquiring.auth', $authParams);

        $this->mockApiClient(
            'reverse',
            function ($paymentId, $requestParams) use ($authParams) {
                $this->assertEquals($authParams, array_intersect($authParams, $requestParams));
                return true;
            },
            ['errorCode' => 0]
        );

        $acquiringPayment = $this->createAcquiringPayment();

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->reverse($acquiringPayment->id);
    }

    /**
     * @test
     */
    public function reverse_method_throws_exception_when_gets_non_existing_payment_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->mockApiClient('reverse', function () {
            return true;
        }, ['errorCode' => 0]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->reverse(99);
    }

    /**
     * @test
     */
    public function reverse_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage("Error updating AcquiringPaymentOperation. Response: {\"errorCode\":0}");

        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('reverse', function () {
            return true;
        }, ['errorCode' => 0]);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $this->createAcquiringPayment();
        $client->reverse($acquiringPayment->id);
    }

    /**
     * @test
     * @dataProvider reverse_method_data_provider
     */
    public function reverse_method_saves_operation_to_db_and_returns_response(
        array $params,
        array $authParams,
        string $method,
        array $headers,
        array $response,
        int $paymentStatusId
    ) {
        $this->setAuthParams($authParams);
        $expectedParams = array_merge($authParams, $params);

        $acquiringPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::CONFIRMED]);

        $this->mockApiClient('reverse', function (
            $requestPaymentId,
            $requestParams,
            $requestMethod,
            $requestHeaders
        ) use (
            $method,
            $expectedParams,
            $headers,
            $acquiringPayment
        ) {
            $this->assertEquals($acquiringPayment->bank_order_id, $requestPaymentId);
            $this->assertEquals($expectedParams, $requestParams);
            $this->assertEquals($method, $requestMethod);
            $this->assertEquals($headers, $requestHeaders);
            return true;
        }, $response);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->reverse($acquiringPayment->id, $params, $method, $headers);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'status_id' => $paymentStatusId,
            'bank_order_id' => $acquiringPayment->bank_order_id,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::REVERSE,
            'request_json' => json_encode(array_merge(['orderId' => $acquiringPayment->bank_order_id], $params)),
            'response_json' => json_encode($response),
        ]);
    }

    /**
     * @test
     * @dataProvider auth_params_data_provider
     */
    public function refund_method_uses_auth_params_from_config(array $authParams)
    {
        Config::set('sberbank-acquiring.auth', $authParams);

        $this->mockApiClient(
            'refund',
            function ($paymentId, $amount, $requestParams) use ($authParams) {
                $this->assertEquals($authParams, array_intersect($authParams, $requestParams));
                return true;
            },
            ['errorCode' => 0]
        );

        $acquiringPayment = $this->createAcquiringPayment();

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->refund($acquiringPayment->id, 1000);
    }

    /**
     * @test
     */
    public function refund_method_throws_exception_when_gets_non_existing_payment_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->mockApiClient('refund', function () {
            return true;
        }, ['errorCode' => 0]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->refund(99, 1000);
    }

    /**
     * @test
     */
    public function refund_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage("Error updating AcquiringPaymentOperation. Response: {\"errorCode\":0}");

        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('refund', function () {
            return true;
        }, ['errorCode' => 0]);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $this->createAcquiringPayment();
        $client->refund($acquiringPayment->id, 5000);
    }

    /**
     * @test
     * @dataProvider refund_method_data_provider
     */
    public function refund_method_saves_operation_to_db_and_returns_response(
        int $amount,
        array $params,
        array $authParams,
        string $method,
        array $headers,
        array $response,
        int $paymentStatusId
    ) {
        $this->setAuthParams($authParams);
        $expectedParams = array_merge($authParams, $params);

        $acquiringPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::CONFIRMED]);

        $this->mockApiClient('refund', function (
            $requestPaymentId,
            $requestAmount,
            $requestParams,
            $requestMethod,
            $requestHeaders
        ) use (
            $amount,
            $method,
            $expectedParams,
            $headers,
            $acquiringPayment
        ) {
            $this->assertEquals($acquiringPayment->bank_order_id, $requestPaymentId);
            $this->assertEquals($amount, $requestAmount);
            $this->assertEquals($expectedParams, $requestParams);
            $this->assertEquals($method, $requestMethod);
            $this->assertEquals($headers, $requestHeaders);
            return true;
        }, $response);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->refund($acquiringPayment->id, $amount, $params, $method, $headers);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'status_id' => $paymentStatusId,
            'bank_order_id' => $acquiringPayment->bank_order_id,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::REFUND,
            'request_json' => json_encode(array_merge([
                'orderId' => $acquiringPayment->bank_order_id,
                'amount' => $amount,
            ], $params)),
            'response_json' => json_encode($response),
        ]);
    }

    /**
     * @test
     * @dataProvider auth_params_data_provider
     */
    public function get_order_status_extended_method_uses_auth_params_from_config(array $authParams)
    {
        Config::set('sberbank-acquiring.auth', $authParams);

        $this->mockApiClient(
            'getOrderStatusExtended',
            function ($requestParams) use ($authParams) {
                $this->assertEquals($authParams, array_intersect($authParams, $requestParams));
                return true;
            },
            ['errorCode' => 0, 'orderStatus' => 1]
        );

        $acquiringPayment = $this->createAcquiringPayment();

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->getOrderStatusExtended($acquiringPayment->id);
    }

    /**
     * @test
     */
    public function get_order_status_extended_method_throws_exception_when_gets_non_existing_payment_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->mockApiClient('getOrderStatusExtended', function () {
            return true;
        }, ['errorCode' => 0, 'orderStatus' => 0]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->getOrderStatusExtended(99);
    }

    /**
     * @test
     */
    public function get_order_status_extended_method_throws_exception_when_receives_unknown_status_id()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage("Unknown \"orderStatus\" \"10\" found in response");

        $this->mockApiClient('getOrderStatusExtended', function () {
            return true;
        }, ['errorCode' => 0, 'orderStatus' => 10]);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $this->createAcquiringPayment();
        $client->getOrderStatusExtended($acquiringPayment->id);
    }

    /**
     * @test
     */
    public function get_order_status_extended_method_throws_exception_and_sets_error_status_when_cannot_save_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage(
            "Error updating AcquiringPaymentOperation. Response: {\"errorCode\":0,\"orderStatus\":1}"
        );

        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('getOrderStatusExtended', function () {
            return true;
        }, ['errorCode' => 0, 'orderStatus' => 1]);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::NEW]);
        $client->getOrderStatusExtended($acquiringPayment->id);
    }

    /**
     * @test
     * @dataProvider get_order_status_extended_method_data_provider
     */
    public function get_order_status_extended_method_saves_operation_to_db_and_returns_updated_payment(
        array $params,
        array $authParams,
        string $method,
        array $headers,
        array $response,
        int $newPaymentStatusId
    ) {
        $this->setAuthParams($authParams);

        $acquiringPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::NEW]);

        $expectedParams = array_merge(['orderId' => $acquiringPayment->bank_order_id], $authParams, $params);

        $this->mockApiClient('getOrderStatusExtended', function (
            $requestParams,
            $requestMethod,
            $requestHeaders
        ) use (
            $method,
            $expectedParams,
            $headers
        ) {
            $this->assertEquals($expectedParams, $requestParams);
            $this->assertEquals($method, $requestMethod);
            $this->assertEquals($headers, $requestHeaders);
            return true;
        }, $response);

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->getOrderStatusExtended($acquiringPayment->id, $params, $method, $headers);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'status_id' => $newPaymentStatusId,
            'bank_order_id' => $acquiringPayment->bank_order_id,
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::GET_EXTENDED_STATUS,
            'request_json' => json_encode(array_merge(['orderId' => $acquiringPayment->bank_order_id], $params)),
            'response_json' => json_encode($response),
        ]);
    }

    /**
     * @test
     */
    public function pay_with_apple_pay_method_uses_merchant_login_params_from_config()
    {
        $merchantLogin = 'test_merchant_login';
        Config::set('sberbank-acquiring.merchant_login', $merchantLogin);

        $this->mockApiClient(
            'payWithApplePay',
            function ($requestMerchantLogin) use ($merchantLogin) {
                $this->assertEquals($merchantLogin, $requestMerchantLogin);
                return true;
            },
            ['data' => ['orderId' => '1vc62']]
        );

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithApplePay('123abc');
    }

    /**
     * @test
     * @dataProvider pay_with_apple_pay_method_data_provider
     */
    public function pay_with_apple_pay_method_saves_payments_to_db_and_returns_response(
        string $merchant,
        string $paymentToken,
        array $params,
        string $method,
        array $headers,
        array $apiResponse,
        int $operationStatusId
    ) {
        Config::set('sberbank-acquiring.merchant_login', $merchant);

        $this->mockApiClient(
            'payWithApplePay',
            function (
                $requestMerchant,
                $requestPaymentToken,
                $requestParams,
                $requestMethod,
                $requestHeaders
            ) use (
                $merchant,
                $paymentToken,
                $params,
                $method,
                $headers
            ) {
                $this->assertEquals($merchant, $requestMerchant);
                $this->assertEquals($paymentToken, $requestPaymentToken);
                $this->assertEquals($params, $requestParams);
                $this->assertEquals($method, $requestMethod);
                $this->assertEquals($headers, $requestHeaders);
                return true;
            },
            $apiResponse
        );

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->payWithApplePay($paymentToken, $params, $method, $headers);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertInstanceOf(ApplePayPayment::class, $acquiringPayment->payment);
        $this->assertInstanceOf(Collection::class, $acquiringPayment->operations);
        $this->assertEquals(1, $acquiringPayment->operations->count());

        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'bank_order_id' => isset($apiResponse['data']) ? $apiResponse['data']['orderId'] : null,
            'status_id' => $operationStatusId,
            'system_id' => DictAcquiringPaymentSystem::APPLE_PAY,
        ]);
        $this->assertDatabaseHas($this->getTableName('apple_pay_payments'), [
            'order_number' => $params['orderNumber'] ?? null,
            'description' => $params['description'] ?? null,
            'language' => $params['language'] ?? null,
            'additional_parameters' => isset($params['additionalParameters']) ? json_encode($params['additionalParameters']) : null,
            'pre_auth' => $params['preAuth'] ?? null,
            'payment_token' => $paymentToken,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::APPLE_PAY_PAYMENT,
            'request_json' => json_encode(array_merge(['paymentToken' => $paymentToken], $params)),
            'response_json' => json_encode($apiResponse),
        ]);
    }

    /**
     * @test
     */
    public function pay_with_apple_pay_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage(
            'Error updating AcquiringPayment. Error updating AcquiringPaymentOperation. Response: {"error":{"code":10}}'
        );

        $user = $this->createUser();
        $this->actingAs($user);

        $acquiringPayment = $this->mockAcquiringPayment('update', false);
        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createAcquiringPayment')->andReturn($acquiringPayment);
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('payWithApplePay', function () {
            return true;
        },
            ['error' => ['code' => 10]]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithApplePay('123');
    }

    /**
     * @test
     */
    public function pay_with_samsung_pay_method_uses_merchant_login_params_from_config()
    {
        $merchantLogin = 'merchant_login';
        Config::set('sberbank-acquiring.merchant_login', $merchantLogin);

        $this->mockApiClient(
            'payWithSamsungPay',
            function ($requestMerchantLogin) use ($merchantLogin) {
                $this->assertEquals($merchantLogin, $requestMerchantLogin);
                return true;
            },
            ['data' => ['orderId' => '123']]
        );

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithSamsungPay('21vc1');
    }

    /**
     * @test
     * @dataProvider pay_with_samsung_pay_method_data_provider
     */
    public function pay_with_samsung_pay_method_saves_payments_to_db_and_returns_response(
        string $merchant,
        string $paymentToken,
        array $params,
        string $method,
        array $headers,
        array $apiResponse,
        int $operationStatusId
    ) {
        Config::set('sberbank-acquiring.merchant_login', $merchant);

        $this->mockApiClient(
            'payWithSamsungPay',
            function (
                $requestMerchant,
                $requestPaymentToken,
                $requestParams,
                $requestMethod,
                $requestHeaders
            ) use (
                $merchant,
                $paymentToken,
                $params,
                $method,
                $headers
            ) {
                $this->assertEquals($merchant, $requestMerchant);
                $this->assertEquals($paymentToken, $requestPaymentToken);
                $this->assertEquals($params, $requestParams);
                $this->assertEquals($method, $requestMethod);
                $this->assertEquals($headers, $requestHeaders);
                return true;
            },
            $apiResponse
        );

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->payWithSamsungPay($paymentToken, $params, $method, $headers);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertInstanceOf(SamsungPayPayment::class, $acquiringPayment->payment);
        $this->assertInstanceOf(Collection::class, $acquiringPayment->operations);
        $this->assertEquals(1, $acquiringPayment->operations->count());

        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'bank_order_id' => isset($apiResponse['data']) ? $apiResponse['data']['orderId'] : null,
            'status_id' => $operationStatusId,
            'system_id' => DictAcquiringPaymentSystem::SAMSUNG_PAY,
        ]);
        $this->assertDatabaseHas($this->getTableName('samsung_pay_payments'), [
            'order_number' => $params['orderNumber'] ?? null,
            'description' => $params['description'] ?? null,
            'language' => $params['language'] ?? null,
            'additional_parameters' => isset($params['additionalParameters']) ? json_encode($params['additionalParameters']) : null,
            'pre_auth' => $params['preAuth'] ?? null,
            'payment_token' => $paymentToken,
            'client_id' => $params['clientId'] ?? null,
            'ip' => $params['ip'] ?? null,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::SAMSUNG_PAY_PAYMENT,
            'request_json' => json_encode(array_merge(['paymentToken' => $paymentToken], $params)),
            'response_json' => json_encode($apiResponse),
        ]);
    }

    /**
     * @test
     */
    public function pay_with_samsung_pay_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage(
            'Error updating AcquiringPayment. Error updating AcquiringPaymentOperation. Response: {"error":{"code":20}}'
        );

        $user = $this->createUser();
        $this->actingAs($user);

        $acquiringPayment = $this->mockAcquiringPayment('update', false);
        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createAcquiringPayment')->andReturn($acquiringPayment);
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('payWithSamsungPay', function () {
            return true;
        },
            ['error' => ['code' => 20]]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithSamsungPay('123');
    }

    /**
     * @test
     */
    public function pay_with_google_pay_method_can_use_config_params()
    {
        Config::set('sberbank-acquiring.params.return_url', 'http://return-url');
        Config::set('sberbank-acquiring.params.fail_url', 'http://fail-url');

        $this->mockApiClient(
            'payWithGooglePay',
            function ($merchant, $token, $amount, $returnUrl, $params) {
                $this->assertEquals('http://return-url', $returnUrl);
                $this->assertEquals('http://fail-url', $params['failUrl']);
                return true;
            },
            ['data' => ['orderId' => 'vcx1v']]
        );

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithGooglePay('123abc', 100);
    }

    /**
     * @test
     */
    public function pay_with_google_pay_method_omits_config_params_when_they_present_in_args()
    {
        Config::set('sberbank-acquiring.params.return_url', 'http://bad-return-url');
        Config::set('sberbank-acquiring.params.fail_url', 'http://bad-fail-url');

        $this->mockApiClient(
            'payWithGooglePay',
            function ($merchant, $token, $amount, $returnUrl, $params) {
                $this->assertEquals('http://return-url', $returnUrl);
                $this->assertEquals('http://fail-url', $params['failUrl']);
                return true;
            },
            ['data' => ['orderId' => 'vcx1v']]
        );

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithGooglePay('123abc', 100, ['returnUrl' => 'http://return-url', 'failUrl' => 'http://fail-url']);
    }

    /**
     * @test
     * @dataProvider pay_with_google_pay_method_data_provider
     */
    public function pay_with_google_pay_method_saves_payments_to_db_and_returns_response(
        string $merchant,
        string $paymentToken,
        int $amount,
        string $returnUrl,
        array $params,
        string $method,
        array $headers,
        array $apiResponse,
        int $operationStatusId
    ) {
        Config::set('sberbank-acquiring.merchant_login', $merchant);

        $this->mockApiClient(
            'payWithGooglePay',
            function (
                $requestMerchant,
                $requestPaymentToken,
                $requestAmount,
                $requestReturnUrl,
                $requestParams,
                $requestMethod,
                $requestHeaders
            ) use (
                $merchant,
                $paymentToken,
                $amount,
                $returnUrl,
                $params,
                $method,
                $headers
            ) {
                $this->assertEquals($amount, $requestAmount);
                $this->assertEquals($returnUrl, $requestReturnUrl);
                $this->assertEquals($merchant, $requestMerchant);
                $this->assertEquals($paymentToken, $requestPaymentToken);
                $this->assertEquals($params, $requestParams);
                $this->assertEquals($method, $requestMethod);
                $this->assertEquals($headers, $requestHeaders);
                return true;
            },
            $apiResponse
        );

        $user = $this->createUser();
        $this->actingAs($user);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $acquiringPayment = $client->payWithGooglePay(
            $paymentToken,
            $amount,
            array_merge(['returnUrl' => $returnUrl,], $params),
            $method,
            $headers
        );

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertInstanceOf(GooglePayPayment::class, $acquiringPayment->payment);
        $this->assertInstanceOf(Collection::class, $acquiringPayment->operations);
        $this->assertEquals(1, $acquiringPayment->operations->count());

        $this->assertDatabaseHas($this->getTableName('payments'), [
            'id' => $acquiringPayment->id,
            'bank_order_id' => isset($apiResponse['data']) ? $apiResponse['data']['orderId'] : null,
            'status_id' => $operationStatusId,
            'system_id' => DictAcquiringPaymentSystem::GOOGLE_PAY,
        ]);
        $this->assertDatabaseHas($this->getTableName('google_pay_payments'), [
            'order_number' => $params['orderNumber'] ?? null,
            'amount' => $amount,
            'description' => $params['description'] ?? null,
            'language' => $params['language'] ?? null,
            'additional_parameters' => isset($params['additionalParameters']) ? json_encode($params['additionalParameters']) : null,
            'pre_auth' => $params['preAuth'] ?? null,
            'payment_token' => $paymentToken,
            'client_id' => $params['clientId'] ?? null,
            'ip' => $params['ip'] ?? null,
            'email' => $params['email'] ?? null,
            'phone' => $params['phone'] ?? null,
            'return_url' => $returnUrl,
            'fail_url' => $params['failUrl'] ?? null,
            'currency_code' => $params['currencyCode'] ?? null,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'payment_id' => $acquiringPayment->id,
            'user_id' => $user->getKey(),
            'type_id' => DictAcquiringPaymentOperationType::GOOGLE_PAY_PAYMENT,
            'request_json' => json_encode(array_merge([
                'paymentToken' => $paymentToken,
                'amount' => $amount,
                'returnUrl' => $returnUrl,
            ], $params)),
            'response_json' => json_encode($apiResponse),
        ]);
    }

    /**
     * @test
     */
    public function pay_with_google_pay_method_throws_exception_when_cannot_update_payment_models_with_response()
    {
        $this->expectException(ResponseProcessingException::class);
        $this->expectExceptionMessage(
            'Error updating AcquiringPayment. Error updating AcquiringPaymentOperation. Response: {"error":{"code":30}}'
        );

        $user = $this->createUser();
        $this->actingAs($user);

        $acquiringPayment = $this->mockAcquiringPayment('update', false);
        $operation = $this->mockAcquiringPaymentOperation('update', false);

        $factory = \Mockery::mock(PaymentsFactory::class)->makePartial();
        $factory->shouldReceive('createAcquiringPayment')->andReturn($acquiringPayment);
        $factory->shouldReceive('createPaymentOperation')->andReturn($operation);
        $this->app->instance(PaymentsFactory::class, $factory);

        $this->mockApiClient('payWithGooglePay', function () {
            return true;
        },
            ['error' => ['code' => 30]]);

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->payWithGooglePay('123', 100);
    }

    public function register_method_data_provider()
    {
        yield [1000, 'http://pay.test.com/pay', [
            'orderNumber' => '',
            'currency' => Currency::USD,
            'failUrl' => 'http://test.com/api/error',
            'description' => 'order description',
            'language' => 'EN',
            'clientId' => '18bnv643',
            'pageView' => 'MOBILE',
            'jsonParams' => ['foo' => 'bar'],
            'sessionTimeoutSecs' => 1200,
            'expirationDate' => '2021-01-02T14:53:02',
            'features' => 'order features',
        ], [
            'userName' => 'test_userName',
            'password' => 'test_password',
        ],
            ['errorCode' => 0, 'orderId' => '1234vs41', 'formUrl' => 'http://pay.test.test/58vcnx'],
            DictAcquiringPaymentStatus::REGISTERED,
        ];

        yield [1000, 'http://pay.test.com/pay', [
            'orderNumber' => 'vc841nvcx',
            'currency' => Currency::USD,
            'failUrl' => 'http://test.com/api/error',
            'description' => 'order description',
            'language' => 'EN',
            'clientId' => '18bnv643',
            'pageView' => 'MOBILE',
            'jsonParams' => ['foo' => 'bar'],
            'sessionTimeoutSecs' => 1200,
            'expirationDate' => '2020-12-01T12:00:00',
            'features' => 'order features',
        ], [
            'token' => 'test_token',
        ],
            ['errorCode' => 0, 'orderId' => '5vc013cx', 'formUrl' => 'http://pay.test.test/17nvcd'],
            DictAcquiringPaymentStatus::REGISTERED,
        ];

        yield [1000, 'http://pay.test/test-pay', [
            'orderNumber' => '0vcxn12',
            'currency' => Currency::RUB,
            'failUrl' => 'http://pay-test.com/api/error',
            'description' => 'order description',
            'language' => 'RU',
            'clientId' => '',
            'pageView' => 'DESKTOP',
            'jsonParams' => [],
            'sessionTimeoutSecs' => 1200,
            'expirationDate' => '',
            'features' => 'order features',
        ], [
            'token' => 'test_token',
        ],
            ['errorCode' => 10, 'errorMessage' => 'system error'],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function auth_params_data_provider()
    {
        yield [['userName' => 'test_userName', 'password' => 'test_password']];
        yield [['token' => 'test_token']];
    }

    public function deposit_method_data_provider()
    {
        yield [
            1000,
            ['param-1' => 'value-1'],
            ['token' => 'deposit_auth_token'],
            HttpClientInterface::METHOD_POST,
            ['header-1' => 'header-1-value'],
            ['errorCode' => 0],
            DictAcquiringPaymentStatus::REGISTERED,
        ];
        yield [
            2000,
            [],
            ['userName' => 'deposit_userName', 'password' => 'deposit_password'],
            HttpClientInterface::METHOD_POST,
            ['header-2' => 'header-2-value'],
            ['error' => ['code' => 0, 'message' => 'success']],
            DictAcquiringPaymentStatus::REGISTERED,
        ];
        yield [
            3000,
            ['param-1' => 'value-1', 'param-2' => 'value-2'],
            ['userName' => 'deposit_userName', 'password' => 'deposit_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success']],
            DictAcquiringPaymentStatus::REGISTERED,
        ];
        yield [
            3000,
            [],
            ['userName' => 'deposit_userName', 'password' => 'deposit_password'],
            HttpClientInterface::METHOD_POST,
            [],
            ['errorCode' => 10, 'errorMessage' => 'error occurred!'],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            3000,
            ['param' => 'value'],
            ['userName' => 'deposit_userName', 'password' => 'deposit_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 10, 'message' => 'success']],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function reverse_method_data_provider()
    {
        yield [
            ['language' => 'RU'],
            ['token' => 'reverse_auth_token'],
            HttpClientInterface::METHOD_POST,
            ['header-1' => 'header-1-value'],
            ['errorCode' => 0],
            DictAcquiringPaymentStatus::REVERSED,
        ];
        yield [
            [],
            ['userName' => 'reverse_userName', 'password' => 'reverse_password'],
            HttpClientInterface::METHOD_POST,
            ['header-2' => 'header-2-value'],
            ['error' => ['code' => 0, 'message' => 'success']],
            DictAcquiringPaymentStatus::REVERSED,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'reverse_userName', 'password' => 'reverse_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success']],
            DictAcquiringPaymentStatus::REVERSED,
        ];
        yield [
            [],
            ['userName' => 'reverse_userName', 'password' => 'reverse_password'],
            HttpClientInterface::METHOD_POST,
            [],
            ['errorCode' => 10, 'errorMessage' => 'error occurred!'],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            [],
            ['userName' => 'reverse_userName', 'password' => 'reverse_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 10, 'message' => 'success']],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function refund_method_data_provider()
    {
        yield [
            5000,
            ['language' => 'DE'],
            ['token' => 'refund_auth_token'],
            HttpClientInterface::METHOD_POST,
            ['header-1' => 'header-1-value'],
            ['errorCode' => 0],
            DictAcquiringPaymentStatus::CONFIRMED,
        ];
        yield [
            1005,
            [],
            ['userName' => 'refund_userName', 'password' => 'refund_password'],
            HttpClientInterface::METHOD_POST,
            ['header-2' => 'header-2-value'],
            ['error' => ['code' => 0, 'message' => 'success']],
            DictAcquiringPaymentStatus::CONFIRMED,
        ];
        yield [
            2100,
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'refund_userName', 'password' => 'refund_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success']],
            DictAcquiringPaymentStatus::CONFIRMED,
        ];
        yield [
            3200,
            ['language' => 'EN'],
            ['userName' => 'refund_userName', 'password' => 'refund_password'],
            HttpClientInterface::METHOD_POST,
            [],
            ['errorCode' => 10, 'errorMessage' => 'error occurred!'],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            5010,
            [],
            ['userName' => 'refund_userName', 'password' => 'refund_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 10, 'message' => 'success']],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function get_order_status_extended_method_data_provider()
    {
        yield [
            ['language' => 'DE'],
            ['token' => 'test_token'],
            HttpClientInterface::METHOD_POST,
            ['header-1' => 'header-1-value'],
            ['errorCode' => 0, 'orderStatus' => 0],
            DictAcquiringPaymentStatus::REGISTERED,
        ];
        yield [
            [],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_POST,
            ['header-2' => 'header-2-value'],
            ['error' => ['code' => 0, 'message' => 'success'], 'orderStatus' => 1],
            DictAcquiringPaymentStatus::HELD,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success'], 'orderStatus' => 2],
            DictAcquiringPaymentStatus::CONFIRMED,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success'], 'orderStatus' => 3],
            DictAcquiringPaymentStatus::REVERSED,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success'], 'orderStatus' => 4],
            DictAcquiringPaymentStatus::REFUNDED,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success'], 'orderStatus' => 5],
            DictAcquiringPaymentStatus::ACS_AUTH,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 0, 'message' => 'success'], 'orderStatus' => 6],
            DictAcquiringPaymentStatus::AUTH_DECLINED,
        ];
        yield [
            ['language' => 'EN', 'additional_param' => 'value'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_GET,
            [],
            ['error' => ['code' => 10]],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            ['language' => 'EN'],
            ['userName' => 'test_userName', 'password' => 'test_password'],
            HttpClientInterface::METHOD_POST,
            [],
            ['errorCode' => 10, 'errorMessage' => 'error occurred!'],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function pay_with_apple_pay_method_data_provider()
    {
        yield [
            'login_31vc-vcx1',
            'v01-bc123vds-x',
            ['language' => 'EN'],
            HttpClientInterface::METHOD_POST,
            [],
            ['data' => ['orderId' => 'np-qe_41sjkg']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_123',
            '91,041vx123-15bcx1241',
            [],
            HttpClientInterface::METHOD_POST,
            [],
            ['data' => ['orderId' => '1cx031vc14']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_123_abc',
            '12m-x41nvd142',
            ['description' => 'order description', 'preAuth' => 'true'],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'text/plain'],
            ['data' => ['orderId' => '1cx031vc14']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_123_abc',
            '194c014245152mc',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'RU',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
            ],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'text/html'],
            ['data' => ['orderId' => 'bvc914mvcx']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_123_abc',
            '194c014245152mc',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'RU',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
            ],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'text/html'],
            ['error' => ['code' => 10, 'message' => 'unknown error occured!']],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            'login_123_abc',
            '194c014245152mc',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'RU',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
            ],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'text/html'],
            ['error' => ['code' => 10, 'message' => 'unknown error occured!']],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function pay_with_samsung_pay_method_data_provider()
    {
        yield [
            'login_41vc1',
            '10bcd-145bcx',
            ['language' => 'RU'],
            HttpClientInterface::METHOD_POST,
            [],
            ['data' => ['orderId' => 'vc81mvcx-0']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_g9m141-0vc',
            '90vmvc2-51cv',
            [],
            HttpClientInterface::METHOD_POST,
            [],
            ['data' => ['orderId' => '0vc_123vc']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_1041',
            '01pvc-14cvx1pq',
            ['description' => 'order description', 'preAuth' => 'true'],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'application/json'],
            ['data' => ['orderId' => 'v8123mvx01']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_v912mvc',
            '91m990951m41vcx',
            [
                'description' => 'some order description',
                'preAuth' => 'true',
                'language' => 'IT',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
                'clientId' => '123cx13478',
            ],
            HttpClientInterface::METHOD_POST,
            ['content-type' => 'application/x-www-form-urlencoded'],
            ['data' => ['orderId' => 'bv9134mbcx']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_123_abc',
            '194c014245152mc',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'RU',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
                'clientId' => '1vcs9421',
                'ip' => '10.10.10.10',
            ],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'text/html'],
            ['error' => ['code' => 10, 'message' => 'unknown error occured!']],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            'login_812n3',
            '0mcbv4145t0441_12314vc',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'DE',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
                'ip' => '10.10.10.10',
            ],
            HttpClientInterface::METHOD_POST,
            ['content-type' => 'application/x-www-form-urlencoded'],
            ['error' => ['code' => 20, 'message' => 'unknown error occurred!']],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    public function pay_with_google_pay_method_data_provider()
    {
        yield [
            'login_18nvc',
            '91vc-13vxza',
            1000,
            'http://return-url',
            ['language' => 'RU'],
            HttpClientInterface::METHOD_POST,
            [],
            ['data' => ['orderId' => 'v9xcm13vc']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_g9m141-0vc',
            '90vmvc2-51cv',
            2500,
            'http://test-pay.test/success',
            [],
            HttpClientInterface::METHOD_GET,
            [],
            ['data' => ['orderId' => '9vck14vc']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_1041',
            '0bvc14vc-12cv',
            15061,
            'https://test-url.com/api/success',
            [
                'description' => 'order description',
                'preAuth' => 'true',
                'ip' => '10.10.10.10',
                'phone' => '79998887766',
                'failUrl' => 'https://test-url.com/fail',
            ],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'application/json'],
            ['data' => ['orderId' => 'vc9143vc']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_v813vcxz',
            '031mbc-31c_142vcx',
            4000,
            'https://some-return-url.com/api',
            [
                'description' => 'some order description',
                'preAuth' => 'true',
                'language' => 'RU',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
                'clientId' => '123cx13478',
                'currencyCode' => Currency::RUB,
            ],
            HttpClientInterface::METHOD_POST,
            ['content-type' => 'application/x-www-form-urlencoded'],
            ['data' => ['orderId' => 'vcx09123x']],
            DictAcquiringPaymentStatus::NEW,
        ];
        yield [
            'login_123_abc',
            '194c014245152mc',
            500,
            'https://pay.test.com/api/success?id=14mvcx123',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'EN',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
                'clientId' => 'vc14242bc',
                'ip' => '10.10.10.10',
                'phone' => '+79998887766',
                'email' => 'test@test.test',
                'currencyCode' => Currency::EUR,
                'failUrl' => 'https://pay.test.com/api/fail?id=14mvcx123',
            ],
            HttpClientInterface::METHOD_GET,
            ['content-type' => 'text/html'],
            ['error' => ['code' => 10, 'message' => 'unknown error occured!']],
            DictAcquiringPaymentStatus::ERROR,
        ];
        yield [
            'login_bc9814cvx',
            '913mvc-123-c_41vdx',
            150,
            'https://test-pay.com/success?orderId=13vcx142',
            [
                'description' => 'some order description',
                'preAuth' => 'false',
                'language' => 'EN',
                'additionalParameters' => ['param_1' => 'value_1', 'param_2' => 'value_2'],
                'ip' => '10.10.10.10',
                'phone' => '+79998887766',
                'email' => 'user@some-domain.com',
                'currencyCode' => Currency::USD,
                'failUrl' => 'https://test-pay.com/fail?orderId=13vcx142',
                'clientId' => '19cxm,143-vc',
            ],
            HttpClientInterface::METHOD_POST,
            ['content-type' => 'application/x-www-form-urlencoded'],
            ['error' => ['code' => 20, 'message' => 'unknown error occurred!']],
            DictAcquiringPaymentStatus::ERROR,
        ];
    }

    private function mockApiClient(string $method, callable $expectedArgs, array $returnValue)
    {
        $apiClient = \Mockery::mock(ApiClient::class . "[$method]");
        $apiClient->shouldReceive($method)
            ->withArgs($expectedArgs)
            ->andReturn(new SberbankResponse(json_encode($returnValue)));

        $this->app->instance(ApiClientInterface::class, $apiClient);
    }

    private function setAuthParams(array $auth = [
        'userName' => 'bad_userName',
        'password' => 'bad_password',
        'token' => 'bad_token',
    ])
    {
        Config::set('sberbank-acquiring.auth', $auth);
    }
}
