<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests;

use Avlyalin\SberbankAcquiring\Client\ApiClient;
use Avlyalin\SberbankAcquiring\Client\ApiClientInterface;
use Avlyalin\SberbankAcquiring\Client\Curl\Curl;
use Avlyalin\SberbankAcquiring\Client\HttpClient;
use Avlyalin\SberbankAcquiring\Client\HttpClientInterface;
use Avlyalin\SberbankAcquiring\Helpers\Currency;

class ApiClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_an_exception_when_username_password_and_token_are_omitted()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = new ApiClient();
        $client->requestWithAuth(ApiClientInterface::PATH_REGISTER);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_gets_invalid_http_client()
    {
        $this->expectException(\InvalidArgumentException::class);
        $httpClient = new \stdClass();
        $client = new ApiClient([
            'userName' => 'test_username',
            'password' => 'test_password',
            'httpClient' => $httpClient,
        ]);
        $client->requestWithAuth(ApiClientInterface::PATH_REGISTER);
    }

    /**
     * @test
     */
    public function it_sends_username_and_password_within_request_data()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return $data['userName'] === 'test_username' && $data['password'] === 'test_password';
        });

        $client = new ApiClient([
            'userName' => 'test_username',
            'password' => 'test_password',
            'httpClient' => $httpClient,
        ]);
        $client->requestWithAuth(ApiClientInterface::PATH_REGISTER);
    }

    /**
     * @test
     */
    public function it_sends_token_within_request_data()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return $data['token'] === 'auth_token';
        });

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
        ]);
        $client->requestWithAuth(ApiClientInterface::PATH_REGISTER);
    }

    /**
     * @test
     */
    public function it_omits_constructor_username_and_password_when_username_and_password_are_present_in_params()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return $data['userName'] === 'params_username' && $data['password'] === 'params_password';
        });

        $client = new ApiClient([
            'userName' => 'constructor_username',
            'password' => 'constructor_password',
            'httpClient' => $httpClient,
        ]);
        $client->requestWithAuth(
            ApiClientInterface::PATH_REGISTER,
            [
                'userName' => 'params_username',
                'password' => 'params_password',
            ]
        );
    }

    /**
     * @test
     */
    public function it_omits_token_from_params_when_username_and_password_are_set_in_constructor()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $data['userName'] === 'constructor_username' &&
                $data['password'] === 'constructor_password' &&
                isset($data['token']) === false
            ) {
                return true;
            }
            return false;
        });

        $client = new ApiClient([
            'userName' => 'constructor_username',
            'password' => 'constructor_password',
            'httpClient' => $httpClient,
        ]);
        $client->requestWithAuth(
            ApiClientInterface::PATH_REGISTER,
            [
                'token' => 'params_token',
            ]
        );
    }

    /**
     * @test
     */
    public function it_omits_token_from_constructor_when_token_set_in_params()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            return $data['token'] === 'params_token';
        });

        $client = new ApiClient([
            'token' => 'constructor_token',
            'httpClient' => $httpClient,
        ]);
        $client->requestWithAuth(
            ApiClientInterface::PATH_REGISTER,
            [
                'token' => 'params_token',
            ]
        );
    }

    /**
     * @test
     */
    public function it_uses_base_uri_from_constructor_args()
    {
        $httpClient = $this->getHttpClientMock(function ($uri) {
            return strpos($uri, 'https://some-test-uri.com/root-path') === 0;
        });

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_uses_production_uri_when_base_uri_param_not_passed()
    {
        $httpClient = $this->getHttpClientMock(function ($uri) {
            return strpos($uri, ApiClient::URI_PROD) === 0;
        });

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
        ]);
        $client->register(1000, 'http://return-url.test');
    }

    /**
     * @test
     */
    public function it_calls_register_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REGISTER &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['amount'] === 1500 &&
                $data['returnUrl'] === 'http://return-url.test/api/success'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0, "orderId": "f5vd34", "formUrl":"http://pay.test-pay.test?id=v44gds"}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->register(1500, 'http://return-url.test/api/success');

        $this->assertEquals([
            'orderId' => 'f5vd34',
            'formUrl' => 'http://pay.test-pay.test?id=v44gds',
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_register_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REGISTER &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::EUR &&
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

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->register(
            1500,
            'http://return-url.test/api/success',
            [
                'orderNumber' => '123fds543',
                'description' => 'register operation description',
                'language' => 'EN',
                'currency' => Currency::EUR,
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/html']]
        );

        $this->assertEquals([
            'orderId' => '5bc91mcx',
            'formUrl' => 'http://pay.test-pay.test?id=bv5d16',
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_register_with_pre_auth_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REGISTER_PRE_AUTH &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['amount'] === 2000 &&
                $data['returnUrl'] === 'http://return-url.test/api/success'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0, "orderId": "9n21cx", "formUrl":"http://pay.test-pay.test?id=gb73wc"}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->registerPreAuth(2000, 'http://return-url.test/api/success');

        $this->assertEquals([
            'orderId' => '9n21cx',
            'formUrl' => 'http://pay.test-pay.test?id=gb73wc',
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_register_with_pre_auth_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REGISTER_PRE_AUTH &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currency'] === Currency::EUR &&
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

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->registerPreAuth(
            2000,
            'http://return-url.test/api/success',
            [
                'orderNumber' => '11142vcv',
                'description' => 'register with pre auth operation description',
                'language' => 'EN',
                'currency' => Currency::EUR,
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/html']]
        );

        $this->assertEquals([
            'orderId' => 'v743vcx',
            'formUrl' => 'http://pay.test-pay.test?id=6r5evc',
        ], $response);
    }

    /**
     * @test
     */
    public function it_calls_deposit_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_DEPOSIT &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '8n421v' &&
                $data['amount'] === 2100
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
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
    public function it_calls_deposit_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_DEPOSIT &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['orderId'] === '8n421v' &&
                $data['amount'] === 2100 &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->deposit(
            '8n421v',
            2100,
            [],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_reverse_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REVERSE &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '5mvc41vc'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
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
    public function it_calls_reverse_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REVERSE &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'IT' &&
                $data['orderId'] === 'vc91mx' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'IT',
        ]);
        $response = $client->reverse(
            'vc91mx',
            ['language' => 'IT'],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'text/plain']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_calls_refund_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REFUND &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '5mvc41vc' &&
                $data['amount'] === 500
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
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
    public function it_calls_refund_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_REFUND &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['orderId'] === 'b9c041v' &&
                $data['amount'] === 500 &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->refund(
            'b9c041v',
            500,
            [],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals([], $response);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_get_order_status_extended_called_without_required_params()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new ApiClient([
            'token' => 'auth_token',
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatusExtended([]);
    }

    /**
     * @test
     */
    public function it_calls_get_order_status_extended_with_order_id_param_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_ORDER_STATUS_EXTENDED &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '1m9cm12'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"amount":1000,"orderStatus":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatusExtended(['orderId' => '1m9cm12']);

        $this->assertEquals(['orderStatus' => 0, 'amount' => 1000], $response);
    }

    /**
     * @test
     */
    public function it_calls_get_order_status_extended_with_order_number_param_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_ORDER_STATUS_EXTENDED &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderNumber'] === '12mcxsm5'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"amount":5000,"orderStatus":1}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatusExtended(['orderNumber' => '12mcxsm5']);

        $this->assertEquals(['orderStatus' => 1, 'amount' => 5000], $response);
    }

    /**
     *
     * @test
     */
    public function it_calls_get_order_status_extended_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_ORDER_STATUS_EXTENDED &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'RU' &&
                $data['orderId'] === '129m41' &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderStatus":2,"amount":5000,"orderDescription":"order description"}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getOrderStatusExtended(
            [
                'orderId' => '129m41',
                'language' => 'RU',
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals(
            [
                'orderStatus' => 2,
                'amount' => 5000,
                'orderDescription' => "order description",
            ],
            $response
        );
    }

    /**
     * @test
     */
    public function it_calls_pay_with_apple_pay_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_APPLE_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['merchant'] === 'merchant_login_123' &&
                $data['paymentToken'] === '12xco94mvcxt53213_dasde1x'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"21cx1421"}}');

        $client = new ApiClient([
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
    public function it_calls_pay_with_apple_pay_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_APPLE_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['language'] === 'RU' &&
                $data['merchant'] === 'merchant_login_142d' &&
                $data['preAuth'] === true &&
                $data['paymentToken'] === '1mvi9cxm.s,mvc912' &&
                $data['description'] === 'pay with apple pay operation description'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"1nmxz9vm+412"}}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->payWithApplePay(
            'merchant_login_142d',
            '1mvi9cxm.s,mvc912',
            [
                'description' => 'pay with apple pay operation description',
                'language' => 'RU',
                'preAuth' => true,
            ]
        );

        $this->assertEquals(['data' => ['orderId' => '1nmxz9vm+412']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_samsung_pay_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_SAMSUNG_PAY &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['merchant'] === 'merchant_login_cx123m' &&
                $data['paymentToken'] === '123m71_41_n12n3'
            ) {
                return true;
            }
            return false;
        }, '{"error":{"code":0},"success":true,"data":{"orderId":"mv9n1242,vcx"}}');

        $client = new ApiClient([
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
    public function it_calls_pay_with_samsung_pay_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_SAMSUNG_PAY &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currencyCode'] === Currency::USD &&
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

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->payWithSamsungPay(
            'merchant_login_bv71n',
            'vc81nmvxc91',
            [
                'description' => 'pay with samsung pay operation description',
                'language' => 'EN',
                'preAuth' => true,
                'currencyCode' => Currency::USD,
            ],
            HttpClientInterface::METHOD_GET,
            [['content-type' => 'application/json']]
        );

        $this->assertEquals(['data' => ['orderId' => 'mv81m-u7n51']], $response);
    }

    /**
     * @test
     */
    public function it_calls_pay_with_google_pay_with_required_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GOOGLE_PAY &&
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

        $client = new ApiClient([
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
    public function it_calls_pay_with_google_pay_with_optional_params_and_returns_response()
    {
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data, $headers) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GOOGLE_PAY &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['language'] === 'EN' &&
                $data['currencyCode'] === Currency::USD &&
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

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->payWithGooglePay(
            'merchant_login_1nvc781',
            'vb8cn1vc12',
            1200,
            'https://test-pay.test/api/success',
            [
                'description' => 'pay with google pay operation description',
                'language' => 'EN',
                'preAuth' => true,
                'currencyCode' => Currency::USD,
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

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_RECEIPT_STATUS &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['orderId'] === '9nv352-mda1'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderId":"9nv352-mda1","receipt":{"receiptStatus":1}}');

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_RECEIPT_STATUS &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['uuid'] === '8213m,-53215-51nmvc-123v'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderId":"m9m1vc-012","receipt":{"receiptStatus":1}}');

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_RECEIPT_STATUS &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['uuid'] === '2139vm-bv-52vc-124bc' &&
                $data['orderId'] === 'v98l,91m6n532' &&
                $data['language'] === 'FR' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"orderId":"v98l,91m6n532","receipt":{"receiptStatus":1}}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getReceiptStatus(
            [
                'orderId' => 'v98l,91m6n532',
                'uuid' => '2139vm-bv-52vc-124bc',
                'language' => 'FR',
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
        $httpClient = $this->getHttpClientMock(function ($uri, $method, $data) {
            if (
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_BIND &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['bindingId'] === '8n518ncsdf'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_BIND &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['bindingId'] === '9m41v0bcdm1' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->bindCard(
            '9m41v0bcdm1',
            [],
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_UNBIND &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['bindingId'] === '19vcm53012'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_UNBIND &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['bindingId'] === '75b4b325' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->unBindCard(
            '75b4b325',
            [],
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_BINDINGS &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['clientId'] === '91-bvc56432'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_BINDINGS &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['clientId'] === '8vcm53-1234cf' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
        ]);
        $response = $client->getBindings(
            '8vcm53-1234cf',
            [],
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_GET_BINDINGS_BY_CARD_OR_ID &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['pan'] === '01mv8n123cx' &&
                $data['language'] === 'DE' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'currency' => Currency::EUR,
        ]);
        $response = $client->getBindingsByCardOrId(
            [
                'pan' => '01mv8n123cx',
                'language' => 'DE',
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_EXTEND_BINDING &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['bindingId'] === '10vmxs121' &&
                $data['newExpiry'] === 202201
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_EXTEND_BINDING &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['bindingId'] === '912nvc82112' &&
                $data['newExpiry'] === 202012 &&
                $data['language'] === 'RU' &&
                $headers === [['content-type' => 'text/plain']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'currency' => Currency::RUB,
        ]);
        $response = $client->extendBinding(
            '912nvc82112',
            202012,
            [
                'language' => 'RU',
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_VERIFY_ENROLLMENT &&
                $method === HttpClientInterface::METHOD_POST &&
                $data['pan'] === '5555-4151-512-5111'
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"enrolled":true,"emitterName":"Emitter bank"}');

        $client = new ApiClient([
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
                $uri === "https://some-test-uri.com/root-path" . ApiClientInterface::PATH_VERIFY_ENROLLMENT &&
                $method === HttpClientInterface::METHOD_GET &&
                $data['pan'] === '5555-4444-3333-2222' &&
                $headers === [['content-type' => 'application/json']]
            ) {
                return true;
            }
            return false;
        }, '{"errorCode":0,"enrolled":true,"emitterName":"Emitter bank"}');

        $client = new ApiClient([
            'token' => 'auth_token',
            'httpClient' => $httpClient,
            'baseUri' => 'https://some-test-uri.com/root-path',
            'language' => 'RU',
            'currency' => Currency::RUB,
        ]);
        $response = $client->verifyEnrollment(
            '5555-4444-3333-2222',
            [],
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
