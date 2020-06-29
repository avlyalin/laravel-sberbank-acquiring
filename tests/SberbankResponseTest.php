<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests;

use Avlyalin\SberbankAcquiring\Client\SberbankResponse;
use Avlyalin\SberbankAcquiring\Exceptions\JsonException;
use Avlyalin\SberbankAcquiring\Exceptions\OperationException;

class SberbankResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_exception_on_null_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse(null);
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_bool_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse(false);
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_malformed_json_string_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse('{"some": "response}');
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_incorrectly_encoded_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse('{\"some\": \"response\"}');
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_non_success_code_and_message_in_response()
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionCode(10);
        $this->expectExceptionMessage('Error');

        $response = new SberbankResponse(
            '{"orderId": "1v8#5g","formUrl":"http://some-url.com","errorCode": 10, "errorMessage": "Error"}'
        );
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_non_success_code_and_message_in_response_2()
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionCode(10);
        $this->expectExceptionMessage('Error');

        $response = new SberbankResponse(
            '{"orderId": "9nfdv","formUrl":"http://some-url.com","error": {"code": 10, "message": "Error"}}'
        );
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_non_success_response_code_in_response()
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionCode(10);

        $response = new SberbankResponse('{"orderId": "h9gmvc","formUrl":"http://some-url.com","errorCode": 10}');
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_non_success_response_code_in_response_2()
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionCode(10);

        $response = new SberbankResponse('{"orderId": "53mv9f","formUrl":"http://some-url.com","error": {"code": 10}}');
        $response->getFormattedResponse();
    }

    /**
     * @test
     */
    public function it_returns_formatted_response_without_status_fields()
    {
        $response = new SberbankResponse(
            '{"orderId": "fgd55m,421","formUrl":"http://some-url.com","errorCode":0,"success":true}'
        );
        $formattedResponse = $response->getFormattedResponse();

        $this->assertEquals([
            'orderId' => 'fgd55m,421',
            "formUrl" => "http://some-url.com",
        ], $formattedResponse);
    }

    /**
     * @test
     */
    public function it_returns_formatted_response_without_status_fields_2()
    {
        $response = new SberbankResponse(
            '{"orderId": "fg98b","formUrl":"http://some-url.com","error":{"code":0},"success":true}'
        );
        $formattedResponse = $response->getFormattedResponse();

        $this->assertEquals([
            'orderId' => 'fg98b',
            "formUrl" => "http://some-url.com",
        ], $formattedResponse);
    }
}
