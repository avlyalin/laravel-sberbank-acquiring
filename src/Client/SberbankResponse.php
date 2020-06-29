<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Client;

use Avlyalin\SberbankAcquiring\Exceptions\JsonException;
use Avlyalin\SberbankAcquiring\Exceptions\OperationException;

class SberbankResponse
{
    /**
     * Код успешной операции
     */
    public const CODE_SUCCESS = 0;

    /**
     * @var mixed
     */
    private $response;

    /**
     * Response constructor.
     *
     * @param mixed $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Тело ответа сервера без полей статуса
     *
     * @return array
     * @throws JsonException
     * @throws OperationException
     */
    public function getFormattedResponse(): array
    {
        if (is_string($this->response) === false) {
            throw new JsonException("Cannot convert response to JSON. Response given: null");
        }
        $response = json_decode($this->response, true);
        $jsonError = json_last_error();
        if ($jsonError !== JSON_ERROR_NONE || is_null($jsonError) || is_null($response)) {
            throw new JsonException("Cannot convert response to JSON. Response given: $this->response");
        }

        if (isset($response['errorCode'])) {
            $errorCode = (int)$response['errorCode'];
        } elseif (isset($response['error']['code'])) {
            $errorCode = (int)$response['error']['code'];
        } else {
            $errorCode = self::CODE_SUCCESS;
        }

        if (isset($response['errorMessage'])) {
            $errorMessage = (string)$response['errorMessage'];
        } elseif (isset($response['error']['message'])) {
            $errorMessage = (string)$response['error']['message'];
        } else {
            $errorMessage = 'Unknown error';
        }

        unset($response['errorCode']);
        unset($response['errorMessage']);
        unset($response['error']);
        unset($response['success']);

        if ($errorCode !== self::CODE_SUCCESS) {
            throw new OperationException($errorMessage, $errorCode);
        }

        return $response;
    }
}
