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
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentOperationType;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
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
            'bank_order_id' => $apiResponse['orderId'],
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
            'bank_form_url' => $apiResponse['formUrl'],
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
            'bank_order_id' => $apiResponse['orderId'],
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
            'bank_form_url' => $apiResponse['formUrl'],
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
            ['errorCode' => 10, 'orderId' => '5vc013cx', 'formUrl' => 'http://pay.test.test/bver4'],
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
