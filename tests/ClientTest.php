<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests;

use Avlyalin\SberbankAcquiring\Client\Client;
use Avlyalin\SberbankAcquiring\Client\ClientInterface;
use Avlyalin\SberbankAcquiring\Client\Curl\Curl;
use Avlyalin\SberbankAcquiring\Client\HttpClient;
use Avlyalin\SberbankAcquiring\Client\HttpClientInterface;
use Avlyalin\SberbankAcquiring\Helpers\Currency;

class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_an_exception_when_username_password_and_token_are_omitted()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = new Client([]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_gets_invalid_http_client()
    {
        $this->expectException(\InvalidArgumentException::class);
        $httpClient = new \stdClass();
        $client = new Client([
            'userName' => 'test_username',
            'password' => 'test_password',
            'httpClient' => $httpClient,
        ]);
    }

    /**
     * @test
     */
    public function it_sends_username_and_password_within_request_data()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return isset($data['userName']) && isset($data['password']);
        });

        $client = new Client([
            'userName' => 'test_username',
            'password' => 'test_password',
            'httpClient' => $httpClient,
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_sends_token_within_request_data()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return isset($data['token']) && $data['token'] === 'auth_token';
        });

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_omits_token_when_username_and_password_are_present()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return $data['userName'] === 'test_username' && $data['password'] === 'test_password';
        });

        $client = new Client([
            'userName' => 'test_username',
            'password' => 'test_password',
            'token' => 'auth_token',
            'httpClient' => $httpClient,
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_uses_custom_options_from_constructor_args()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                strpos($uri, 'https://some-test-uri.com/root-path') === 0 &&
                $data['language'] === 'DE' &&
                $data['currency'] === 'DEM'
            ) {
                return true;
            }
            return false;
        });

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'DE',
            'currency' => 'DEM',
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_uses_production_uri_when_base_uri_option_not_passed()
    {
        $httpClient = $this->getHttpClientMock(function ($uri) {
            return strpos($uri, Client::URI_PROD) === 0;
        });

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_calls_register_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REGISTER &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['amount'] === 1500 &&
                $data['returnUrl'] === 'http://return-url.test/api/success'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0, "orderId": "f5vd34", "formUrl":"http://pay.test-pay.test?id=v44gds"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->register(1500, 'http://return-url.test/api/success');

        $this->assertEquals([
            "orderId" => "f5vd34",
            "formUrl" => "http://pay.test-pay.test?id=v44gds",
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_register_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REGISTER &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['amount'] === 1500 &&
                $data['returnUrl'] === 'http://return-url.test/api/success' &&
                $data['orderNumber'] === '123fds543' &&
                $data['description'] === 'register operation description' &&
                $headers === [['content-type' => 'text/html']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0, "orderId": "5bc91mcx", "formUrl":"http://pay.test-pay.test?id=bv5d16"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
        ]);
        $response = $client->register(
            1500,
            'http://return-url.test/api/success',
            ['orderNumber' => '123fds543', 'description' => 'register operation description'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/html']]
        );

        $this->assertEquals([
            "orderId" => "5bc91mcx",
            "formUrl" => "http://pay.test-pay.test?id=bv5d16",
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_register_with_pre_auth_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REGISTER_PRE_AUTH &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['amount'] === 2000 &&
                $data['returnUrl'] === 'http://return-url.test/api/success'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0, "orderId": "9n21cx", "formUrl":"http://pay.test-pay.test?id=gb73wc"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->registerPreAuth(2000, 'http://return-url.test/api/success');

        $this->assertEquals([
            "orderId" => "9n21cx",
            "formUrl" => "http://pay.test-pay.test?id=gb73wc",
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_register_with_pre_auth_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REGISTER_PRE_AUTH &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['amount'] === 2000 &&
                $data['returnUrl'] === 'http://return-url.test/api/success' &&
                $data['orderNumber'] === '11142vcv' &&
                $data['description'] === 'register with pre auth operation description' &&
                $headers === [['content-type' => 'text/html']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0, "orderId": "v743vcx", "formUrl":"http://pay.test-pay.test?id=6r5evc"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
        ]);
        $response = $client->registerPreAuth(
            2000,
            'http://return-url.test/api/success',
            ['orderNumber' => '11142vcv', 'description' => 'register with pre auth operation description'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/html']]
        );

        $this->assertEquals([
            "orderId" => "v743vcx",
            "formUrl" => "http://pay.test-pay.test?id=6r5evc",
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_deposit_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_DEPOSIT &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '8n421v' &&
                $data['amount'] === 2100
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->deposit('8n421v', 2100);

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_deposit_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_DEPOSIT &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::EUR &&
                $data['orderId'] === '8n421v' &&
                $data['amount'] === 2100 &&
                $data['param'] === 'additional deposit operation param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
            'currency' => Currency::EUR,
        ]);
        $response = $client->deposit(
            '8n421v',
            2100,
            ['param' => 'additional deposit operation param'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_reverse_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REVERSE &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '5mvc41vc'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->reverse('5mvc41vc');

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_reverse_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REVERSE &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'IT' &&
                $data['currency'] === Currency::USD &&
                $data['orderId'] === 'vc91mx' &&
                $data['param'] === 'additional reverse operation param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'IT',
            'currency' => Currency::USD,
        ]);
        $response = $client->reverse(
            'vc91mx',
            ['param' => 'additional reverse operation param'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_refund_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REFUND &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '5mvc41vc' &&
                $data['amount'] === 500
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->refund('5mvc41vc', 500);

        $this->assertEquals([], $response);
    }

    /**
     *
     * @test
     */
    public function it_calls_refund_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_REFUND &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::USD &&
                $data['orderId'] === 'b9c041v' &&
                $data['amount'] === 500 &&
                $data['param'] === 'additional refund operation param' &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
            'currency' => Currency::USD,
        ]);
        $response = $client->refund(
            'b9c041v',
            500,
            ['param' => 'additional refund operation param'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_get_order_status_called_without_required_args()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new Client([
            'token' => 'auth_token',
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatus([]);
    }

    /**
     * @test
     */
    public function it_calls_get_order_status_with_order_id_param_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_ORDER_STATUS_EXTENDED &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '1m9cm12'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"amount":1000,"orderStatus":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatus(['orderId' => '1m9cm12']);

        $this->assertEquals(['orderStatus' => 0, 'amount' => 1000], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_order_status_with_order_number_param_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_ORDER_STATUS_EXTENDED &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderNumber'] === '12mcxsm5'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"amount":5000,"orderStatus":1}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatus(['orderNumber' => '12mcxsm5']);

        $this->assertEquals(['orderStatus' => 1, 'amount' => 5000], $response);
    }

    /**
     *
     * @test
     */
    public function it_calls_get_order_status_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_ORDER_STATUS_EXTENDED &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'RU' &&
                $data['currency'] === Currency::RUB &&
                $data['orderId'] === '129m41' &&
                $data['param'] === 'additional get order status operation param' &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderStatus":2,"amount":5000,"orderDescription":"order description"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'RU',
            'currency' => Currency::RUB,
        ]);
        $response = $client->getOrderStatus(
            [
                'orderId' => '129m41',
                'param' => 'additional get order status operation param',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals(
            [
                "orderStatus" => 2,
                "amount" => 5000,
                "orderDescription" => "order description",
            ],
            $response
        );
    }

    /**
     * @test
     */
    public function it_calls_pay_with_apple_pay_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_APPLE_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['merchant'] === 'merchant_login_123' &&
                $data['paymentToken'] === '12xco94mvcxt53213_dasde1x'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"21cx1421"}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->payWithApplePay('merchant_login_123', '12xco94mvcxt53213_dasde1x');

        $this->assertEquals(['data' => ['orderId' => '21cx1421']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_apple_pay_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_APPLE_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['language'] === 'RU' &&
                $data['currency'] === Currency::RUB &&
                $data['merchant'] === 'merchant_login_142d' &&
                $data['preAuth'] === true &&
                $data['paymentToken'] === '1mvi9cxm.s,mvc912' &&
                $data['description'] === 'pay with apple pay operation description'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"1nmxz9vm+412"}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'RU',
            'currency' => Currency::RUB,
        ]);
        $response = $client->payWithApplePay(
            'merchant_login_142d',
            '1mvi9cxm.s,mvc912',
            [
                'preAuth' => true,
                'description' => 'pay with apple pay operation description',
            ]
        );

        $this->assertEquals(['data' => ['orderId' => '1nmxz9vm+412']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_samsung_pay_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_SAMSUNG_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['merchant'] === 'merchant_login_cx123m' &&
                $data['paymentToken'] === '123m71_41_n12n3'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"mv9n1242,vcx"}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->payWithSamsungPay('merchant_login_cx123m', '123m71_41_n12n3');

        $this->assertEquals(['data' => ['orderId' => 'mv9n1242,vcx']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_samsung_pay_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_SAMSUNG_PAY &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::USD &&
                $data['merchant'] === 'merchant_login_bv71n' &&
                $data['preAuth'] === true &&
                $data['paymentToken'] === 'vc81nmvxc91' &&
                $data['description'] === 'pay with samsung pay operation description' &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"mv81m-u7n51"}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
            'currency' => Currency::USD,
        ]);
        $response = $client->payWithSamsungPay(
            'merchant_login_bv71n',
            'vc81nmvxc91',
            [
                'preAuth' => true,
                'description' => 'pay with samsung pay operation description',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals(['data' => ['orderId' => 'mv81m-u7n51']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_google_pay_with_required_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GOOGLE_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['merchant'] === 'merchant_login_bv81m' &&
                $data['paymentToken'] === '9m120vcnd-543m' &&
                $data['amount'] === 1200 &&
                $data['returnUrl'] === 'https://test-pay.test/api/success'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"vun441m,c"}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->payWithGooglePay(
            'merchant_login_bv81m',
            '9m120vcnd-543m',
            1200,
            'https://test-pay.test/api/success'
        );

        $this->assertEquals(['data' => ['orderId' => 'vun441m,c']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_google_pay_with_optional_args_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GOOGLE_PAY &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::USD &&
                $data['merchant'] === 'merchant_login_1nvc781' &&
                $data['amount'] === 1200 &&
                $data['returnUrl'] === 'https://test-pay.test/api/success' &&
                $data['preAuth'] === true &&
                $data['paymentToken'] === 'vb8cn1vc12' &&
                $data['description'] === 'pay with google pay operation description' &&
                $headers === [['content-type' => 'text/html']]
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"v81-8bvm532"}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
            'currency' => Currency::USD,
        ]);
        $response = $client->payWithGooglePay(
            'merchant_login_1nvc781',
            'vb8cn1vc12',
            1200,
            'https://test-pay.test/api/success',
            [
                'preAuth' => true,
                'description' => 'pay with google pay operation description',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/html']]
        );

        $this->assertEquals(['data' => ['orderId' => 'v81-8bvm532']], $response);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_calls_get_receipt_status_without_required_params()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new Client([
            'token' => 'auth_token',
        ]);
        $response = $client->getReceiptStatus([]);
    }

    /**
     * @test
     */
    public function it_calls_get_receipt_status_with_order_id_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_RECEIPT_STATUS &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '9nv352-mda1'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderId":"9nv352-mda1","receipt":{"receiptStatus":1}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getReceiptStatus(['orderId' => '9nv352-mda1']);

        $this->assertEquals([
            'orderId' => '9nv352-mda1',
            'receipt' => ["receiptStatus" => 1],
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_receipt_status_with_uuid_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_RECEIPT_STATUS &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['uuid'] === '8213m,-53215-51nmvc-123v'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderId":"m9m1vc-012","receipt":{"receiptStatus":1}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getReceiptStatus(['uuid' => '8213m,-53215-51nmvc-123v']);

        $this->assertEquals([
            'orderId' => 'm9m1vc-012',
            'receipt' => ["receiptStatus" => 1],
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_receipt_status_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_RECEIPT_STATUS &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['uuid'] === '2139vm-bv-52vc-124bc' &&
                $data['orderId'] === 'v98l,91m6n532' &&
                $data['language'] === 'FR' &&
                $data['currency'] === Currency::EUR &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderId":"v98l,91m6n532","receipt":{"receiptStatus":1}}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'FR',
            'currency' => Currency::EUR,
        ]);
        $response = $client->getReceiptStatus(
            [
                'orderId' => 'v98l,91m6n532',
                'uuid' => '2139vm-bv-52vc-124bc',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([
            'orderId' => 'v98l,91m6n532',
            'receipt' => ["receiptStatus" => 1],
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_bind_card_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_BIND &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['bindingId'] === '8n518ncsdf'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->bindCard('8n518ncsdf');

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_bind_card_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_BIND &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['bindingId'] === '9m41v0bcdm1' &&
                $data['language'] === 'RU' &&
                $data['currency'] === Currency::RUB &&
                $data['param'] === 'optional param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'RU',
            'currency' => Currency::RUB,
        ]);
        $response = $client->bindCard(
            '9m41v0bcdm1',
            ['param' => 'optional param'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_unbind_card_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_UNBIND &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['bindingId'] === '19vcm53012'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->unBindCard('19vcm53012');

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_unbind_card_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_UNBIND &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['bindingId'] === '75b4b325' &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::USD &&
                $data['param'] === 'optional param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'EN',
            'currency' => Currency::USD,
        ]);
        $response = $client->unBindCard(
            '75b4b325',
            ['param' => 'optional param'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_bindings_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_BINDINGS &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['clientId'] === '91-bvc56432'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getBindings('91-bvc56432');

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_bindings_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_BINDINGS &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['clientId'] === '8vcm53-1234cf' &&
                $data['language'] === 'IT' &&
                $data['currency'] === Currency::EUR &&
                $data['param'] === 'optional param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'IT',
            'currency' => Currency::EUR,
        ]);
        $response = $client->getBindings(
            '8vcm53-1234cf',
            ['param' => 'optional param'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_bindings_by_card_or_id_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_GET_BINDINGS_BY_CARD_OR_ID &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['pan'] === '01mv8n123cx' &&
                $data['language'] === 'DE' &&
                $data['currency'] === Currency::EUR &&
                $data['param'] === 'optional param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'DE',
            'currency' => Currency::EUR,
        ]);
        $response = $client->getBindingsByCardOrId(
            [
                'pan' => '01mv8n123cx',
                'param' => 'optional param',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_extend_binding_by_card_or_id_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_EXTEND_BINDING &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['bindingId'] === '10vmxs121' &&
                $data['newExpiry'] === 202201
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'DE',
            'currency' => Currency::EUR,
        ]);
        $response = $client->extendBinding('10vmxs121', 202201);

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_extend_binding_by_card_or_id_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_EXTEND_BINDING &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['bindingId'] === '912nvc82112' &&
                $data['newExpiry'] === 202012 &&
                $data['language'] === 'RU' &&
                $data['currency'] === Currency::RUB &&
                $data['param'] === 'optional param' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'RU',
            'currency' => Currency::RUB,
        ]);
        $response = $client->extendBinding(
            '912nvc82112',
            202012,
            [
                'param' => 'optional param',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_verify_enrollment_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_VERIFY_ENROLLMENT &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['pan'] === '5555-4151-512-5111'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"enrolled":true,"emitterName":"Emitter bank"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->verifyEnrollment('5555-4151-512-5111');

        $this->assertEquals(['enrolled' => true, 'emitterName' => 'Emitter bank'], $response);
    }

    /**
     * @test
     */
    public function it_calls_verify_enrollment_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ClientInterface::PATH_VERIFY_ENROLLMENT &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['pan'] === '5555-4444-3333-2222' &&
                $data['language'] === 'RU' &&
                $data['currency'] === Currency::RUB &&
                $data['param'] === 'optional param' &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"enrolled":true,"emitterName":"Emitter bank"}');

        $client = new Client([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'RU',
            'currency' => Currency::RUB,
        ]);
        $response = $client->verifyEnrollment(
            '5555-4444-3333-2222',
            [
                'param' => 'optional param',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals(['enrolled' => true, 'emitterName' => 'Emitter bank'], $response);
    }

    private function getHttpClientMock(
        callable $expectedRequestArgs,
        string $response = '{"errorCode":0}'
    ) {
        $httpClient = \Mockery::mock(HttpClient::class . "[request]", [new Curl()]);
        $httpClient->shouldReceive('request')
            ->withArgs($expectedRequestArgs)
            ->andReturn($response);
        return $httpClient;
    }
}
