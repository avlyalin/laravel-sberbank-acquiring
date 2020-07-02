<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Client;

use Avlyalin\SberbankAcquiring\Exceptions\JsonException;

class SberbankResponse
{
    /**
     * Код успешной операции
     */
    public const CODE_SUCCESS = 0;

    /**
     * Неизвестная ошибка
     */
    public const UNKNOWN_ERROR_MESSAGE = 'Unknown error';

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
     * Тело ответа сервера
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Тело ответа сервера в виде массива без полей статуса
     *
     * @return array
     * @throws JsonException
     */
    public function getFormattedResponseArray(): array
    {
        $response = $this->getResponseArray();

        unset($response['errorCode']);
        unset($response['errorMessage']);
        unset($response['error']);
        unset($response['success']);

        return $response;
    }

    /**
     * Тело ответа сервера в виде массива
     *
     * @return mixed
     * @throws JsonException
     */
    public function getResponseArray()
    {
        if (is_string($this->response) === false) {
            throw new JsonException("Cannot convert response to JSON. Response given: $this->response");
        }

        $response = json_decode($this->response, true);
        $jsonError = json_last_error();
        if ($jsonError !== JSON_ERROR_NONE || is_null($jsonError) || is_null($response)) {
            throw new JsonException("Cannot convert response to JSON. Response given: $this->response");
        }

        return $response;
    }

    /**
     * Возвращает флаг успешного выполения операции
     *
     * @return bool
     * @throws JsonException
     */
    public function isOk(): bool
    {
        return $this->getErrorCode() === self::CODE_SUCCESS;
    }

    /**
     * Код ошибки
     *
     * @return int
     * @throws JsonException
     */
    public function getErrorCode(): int
    {
        $response = $this->getResponseArray();

        if (isset($response['errorCode'])) {
            return (int)$response['errorCode'];
        } elseif (isset($response['error']['code'])) {
            return (int)$response['error']['code'];
        } else {
            return self::CODE_SUCCESS;
        }
    }

    /**
     * Сообщение об ошибке
     *
     * @return string
     * @throws JsonException
     */
    public function getErrorMessage(): string
    {
        $response = $this->getResponseArray();

        if (isset($response['errorMessage'])) {
            return (string)$response['errorMessage'];
        } elseif (isset($response['error']['message'])) {
            return (string)$response['error']['message'];
        } else {
            return self::UNKNOWN_ERROR_MESSAGE;
        }
    }
}
