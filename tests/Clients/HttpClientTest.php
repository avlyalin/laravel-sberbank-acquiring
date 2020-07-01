<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Clients;

use Avlyalin\SberbankAcquiring\Client\Curl\Curl;
use Avlyalin\SberbankAcquiring\Client\HttpClient;
use Avlyalin\SberbankAcquiring\Exceptions\HttpClientException;
use Avlyalin\SberbankAcquiring\Exceptions\NetworkException;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class HttpClientTest extends TestCase
{

    /**
     * @test
     */
    public function throws_exception_when_gets_empty_uri()
    {
        $this->expectException(\InvalidArgumentException::class);

        $curl = $this->createMock(Curl::class);
        $client = new HttpClient($curl);
        $client->request('');
    }

    /**
     * @test
     */
    public function throws_exception_when_gets_invalid_http_method()
    {
        $this->expectException(\InvalidArgumentException::class);

        $curl = $this->createMock(Curl::class);
        $client = new HttpClient($curl);
        $client->request('http://example.com/api/test', 'HEAD');
    }

    /**
     * @test
     */
    public function throws_exception_on_error_response()
    {
        $this->expectException(NetworkException::class);

        $curl = \Mockery::mock(Curl::class)->makePartial();
        $curl->shouldReceive('execute')->andReturn(false);

        $client = new HttpClient($curl);
        $client->request('http://example.com/api/test');
    }

    /**
     * @test
     */
    public function throws_exception_on_response_status_code_not_200()
    {
        $this->expectException(HttpClientException::class);

        $curl = \Mockery::mock(Curl::class)->makePartial();
        $curl->shouldReceive('execute');
        $curl->shouldReceive('getInfo')->with(CURLINFO_RESPONSE_CODE)->andReturn(400);

        $client = new HttpClient($curl);
        $client->request('http://example.com/api/test');
    }

    /**
     * @test
     */
    public function can_send_get_type_request()
    {
        $curl = \Mockery::mock(Curl::class)->makePartial();
        $curl->shouldReceive('getInfo')->with(CURLINFO_RESPONSE_CODE)->andReturn(200);
        $curl->shouldReceive('execute')->andReturn('{"some":"response"}');
        $curl->expects()->setHeader(['Content-type' => 'application/json']);
        $curl->expects()->setOption(CURLOPT_URL, 'http://example.com/api/test?param_1=value_1&param_2=value_2');

        $client = new HttpClient($curl);
        $response = $client->request(
            'http://example.com/api/test',
            HttpClient::METHOD_GET,
            ['param_1' => 'value_1', 'param_2' => 'value_2']
        );

        $this->assertEquals('{"some":"response"}', $response);
    }

    /**
     * @test
     */
    public function can_send_post_type_request()
    {
        $curl = \Mockery::mock(Curl::class)->makePartial();
        $curl->shouldReceive('getInfo')->with(CURLINFO_RESPONSE_CODE)->andReturn(200);
        $curl->shouldReceive('execute')->andReturn('{"some":"response"}');
        $curl->expects()->setOption(CURLOPT_URL, 'http://example.com/api/test');
        $curl->expects()->setOption(CURLOPT_POST, 1);
        $curl->expects()->setOption(CURLOPT_POSTFIELDS, 'param_1=value_1&param_2=value_2');
        $curl->expects()->setHeader(['Content-type' => 'application/x-www-form-urlencoded']);

        $client = new HttpClient($curl);
        $response = $client->request(
            'http://example.com/api/test',
            HttpClient::METHOD_POST,
            ['param_1' => 'value_1', 'param_2' => 'value_2']
        );

        $this->assertEquals('{"some":"response"}', $response);
    }


    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }
}
