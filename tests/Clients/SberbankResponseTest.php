<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Clients;

use Avlyalin\SberbankAcquiring\Client\SberbankResponse;
use Avlyalin\SberbankAcquiring\Exceptions\JsonException;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class SberbankResponseTest extends TestCase
{
    /**
     * @test
     */
    public function get_response_method_returns_unmodified_string_response()
    {
        $response = new SberbankResponse(
            '{"orderId": "v71n2vc","formUrl":"http://some-url.com","errorCode": 20, "errorMessage": "Some error"}'
        );

        $this->assertEquals(
            '{"orderId": "v71n2vc","formUrl":"http://some-url.com","errorCode": 20, "errorMessage": "Some error"}',
            $response->getResponse()
        );
    }

    /**
     * @test
     */
    public function get_response_method_returns_unmodified_null_response()
    {
        $response = new SberbankResponse(null);

        $this->assertEquals(
            null,
            $response->getResponse()
        );
    }

    /**
     * @test
     */
    public function get_response_array_method_throws_exception_on_null_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse(null);
        $response->getResponseArray();
    }

    /**
     * @test
     */
    public function get_response_array_method_throws_exception_on_bool_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse(false);
        $response->getResponseArray();
    }

    /**
     * @test
     */
    public function get_response_array_method_throws_exception_on_malformed_json_string_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse('{"some": "response}');
        $response->getResponseArray();
    }

    /**
     * @test
     */
    public function get_response_array_method_throws_exception_on_incorrectly_encoded_response()
    {
        $this->expectException(JsonException::class);

        $response = new SberbankResponse('{\"some\": \"response\"}');
        $response->getResponseArray();
    }

    /**
     * @test
     */
    public function get_formatted_response_array_method_returns_formatted_response_array()
    {
        $response = new SberbankResponse(
            '{"orderId": "1v8#5g","formUrl":"http://some-url.com","errorCode": 10, "errorMessage": "Error"}'
        );

        $this->assertEquals([
            'orderId' => '1v8#5g',
            'formUrl' => 'http://some-url.com',
        ], $response->getFormattedResponseArray());
    }

    /**
     * @test
     */
    public function get_formatted_response_array_method_returns_formatted_response_array_2()
    {
        $response = new SberbankResponse(
            '{"orderId": "9nfdv","formUrl":"http://some-url.com","error": {"code": 10, "message": "Error"}}'
        );

        $this->assertEquals([
            'orderId' => '9nfdv',
            'formUrl' => 'http://some-url.com',
        ], $response->getFormattedResponseArray());
    }

    /**
     * @test
     */
    public function get_formatted_response_array_method_returns_formatted_response_array_3()
    {
        $response = new SberbankResponse('{"orderId": "vc91mx","formUrl":"http://test-url.com","errorCode": 0}');

        $this->assertEquals([
            'orderId' => 'vc91mx',
            'formUrl' => 'http://test-url.com',
        ], $response->getFormattedResponseArray());
    }

    /**
     * @test
     */
    public function get_error_code_returns_code()
    {
        $response = new SberbankResponse(
            '{"orderId": "vc81cx","formUrl":"http://some-url.com","errorCode":10,"success":false}'
        );

        $this->assertEquals(10, $response->getErrorCode());
    }

    /**
     * @test
     */
    public function get_error_code_returns_code_2()
    {
        $response = new SberbankResponse(
            '{"orderId": "fgd55m,421","formUrl":"http://some-url.com","error":{"code":20},"success":true}'
        );

        $this->assertEquals(20, $response->getErrorCode());
    }

    /**
     * @test
     */
    public function get_error_code_returns_success_code_when_code_is_missing_in_response()
    {
        $response = new SberbankResponse(
            '{"orderId": "fgd55m,421","formUrl":"http://some-url.com","success":true}'
        );

        $this->assertEquals(SberbankResponse::CODE_SUCCESS, $response->getErrorCode());
    }

    /**
     * @test
     */
    public function get_error_message_returns_message()
    {
        $response = new SberbankResponse(
            '{"orderId": "1325vb","formUrl":"http://some-url.com","errorCode":10,"errorMessage":"error occured"}'
        );

        $this->assertEquals('error occured', $response->getErrorMessage());
    }

    /**
     * @test
     */
    public function get_error_message_returns_message_2()
    {
        $response = new SberbankResponse(
            '{"orderId": "f91ca","formUrl":"http://some-url.com","error":{"code":20, "message":"error!"},"success":true}'
        );

        $this->assertEquals('error!', $response->getErrorMessage());
    }

    /**
     * @test
     */
    public function get_error_message_returns_unknown_error_message_when_message_is_missing_in_response()
    {
        $response = new SberbankResponse(
            '{"orderId": "ow55d","formUrl":"http://some-url.com","error":{"code":20},"success":true}'
        );

        $this->assertEquals(SberbankResponse::UNKNOWN_ERROR_MESSAGE, $response->getErrorMessage());
    }

    /**
     * @test
     */
    public function is_ok_method_returns_operation_status()
    {
        $response = new SberbankResponse(
            '{"orderId": "vqx151","formUrl":"http://some-url.com","errorCode":30}'
        );

        $this->assertEquals(false, $response->isOk());
    }

    /**
     * @test
     */
    public function is_ok_method_returns_operation_status_2()
    {
        $response = new SberbankResponse(
            '{"orderId": "14cbqx","formUrl":"http://some-url.com","error":{"code":20}}'
        );

        $this->assertEquals(false, $response->isOk());
    }

    /**
     * @test
     */
    public function is_ok_method_returns_operation_status_3()
    {
        $response = new SberbankResponse(
            '{"orderId": "bnv74","formUrl":"http://some-url.com","error":{"code":0}}'
        );

        $this->assertEquals(true, $response->isOk());
    }

    /**
     * @test
     */
    public function is_ok_method_returns_operation_status_4()
    {
        $response = new SberbankResponse(
            '{"orderId": "bbcx75","formUrl":"http://some-url.com","errorCode":0}'
        );

        $this->assertEquals(true, $response->isOk());
    }
}
