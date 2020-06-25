<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Client\Curl;

interface CurlInterface
{
    /**
     * Инициализирует сеанс CURL
     *
     * @param string $url URL
     *
     * @return mixed
     */
    public function initialize(string $url = '');

    /**
     * Устанавливает несколько параметров для сеанса CURL
     *
     * @param array $options Массив, определяющий устанавливаемые параметры и их значения
     *
     * @return mixed
     */
    public function setOptions(array $options);

    /**
     * Устанавливает параметр для сеанса CURL
     *
     * @param int $option  Устанавливаемый параметр CURLOPT_XXX
     * @param mixed $value Значение параметра option
     *
     * @return mixed
     */
    public function setOption(int $option, $value);

    /**
     * Устанавливает хэдеры
     *
     * @param array $header
     *
     * @return mixed
     */
    public function setHeader(array $header);

    /**
     * Выполняет запрос CURL
     *
     * @return mixed
     */
    public function execute();

    /**
     * Возвращает информацию об определенной операции
     *
     * @param int $option Константа - опция CURL
     *
     * @return mixed
     */
    public function getInfo(int $option);

    /**
     * Возвращает строку с описанием последней ошибки текущего сеанса
     *
     * @return mixed
     */
    public function getError();

    /**
     * Возвращает код последней ошибки
     *
     * @return mixed
     */
    public function getErrno();

    /**
     * Завершает сеанс CURL
     *
     * @return mixed
     */
    public function close();
}
