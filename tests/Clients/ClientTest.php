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

        $this->assertDatabaseHas($this->getTableName('payments'), [
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
            'status_id' => DictAcquiringPaymentStatus::NEW,
        ]);
        $this->assertDatabaseHas($this->getTableName('sberbank_payments'), [
            'amount' => $amount,
            'return_url' => $returnUrl,
            'fail_url' => $failUrl,
        ]);
        $this->assertDatabaseHas($this->getTableName('payment_operations'), [
            'user_id' => $user->getKey(),
            'request_json' => json_encode([
                'amount' => $amount,
                'returnUrl' => $returnUrl,
                'failUrl' => $failUrl,
            ]),
        ]);
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
        yield [1000, 'http://pay.test.com/pay', ['userName' => 'test_userName', 'password' => 'test_password']];
        yield [1000, 'http://pay.test.com/pay', ['token' => 'test_token']];
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
