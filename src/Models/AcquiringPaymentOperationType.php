<?php

namespace Avlyalin\SberbankAcquiring\Models;

class AcquiringPaymentOperationType extends BaseModel
{
    /**
     * Регистрация заказа
     *
     * @var int
     */
    public const REGISTER = 1;

    /**
     * Регистрация заказа с предавторизацией
     *
     * @var int
     */
    public const REGISTER_PRE_AUTH = 2;

    /**
     * Запрос завершения оплаты заказа
     *
     * @var int
     */
    public const DEPOSIT = 3;

    /**
     * Запрос отмены оплаты заказа
     *
     * @var int
     */
    public const REVERSE = 4;

    /**
     * Запрос возврата средств оплаты заказа
     *
     * @var int
     */
    public const REFUND = 5;

    /**
     * Расширенный запрос состояния заказа
     *
     * @var int
     */
    public const GET_EXTENDED_STATUS = 6;

    /**
     * Запрос оплаты через Apple Pay
     *
     * @var int
     */
    public const APPLE_PAY_PAYMENT = 7;

    /**
     * Запрос оплаты через Samsung Pay
     *
     * @var int
     */
    public const SAMSUNG_PAY_PAYMENT = 8;

    /**
     * Запрос оплаты через Google Pay
     *
     * @var int
     */
    public const GOOGLE_PAY_PAYMENT = 9;

    /**
     * Запрос сведений о кассовом чеке
     *
     * @var int
     */
    public const GET_RECEIPT_STATUS = 10;

    /**
     * Запрос активации связки
     *
     * @var int
     */
    public const BIND_REQUEST = 11;

    /**
     * Запрос деактивации связки
     *
     * @var int
     */
    public const UNBIND_REQUEST = 12;

    /**
     * Запрос списка всех связок клиента
     *
     * @var int
     */
    public const GET_BINDINGS_BY_CLIENT = 13;

    /**
     * Запрос списка связок определённой банковской карты
     *
     * @var int
     */
    public const GET_BINDINGS_BY_CARD = 14;

    /**
     * Запрос изменения срока действия связки
     *
     * @var int
     */
    public const EXTEND_BINDING = 15;

    /**
     * Запрос проверки вовлечённости карты в 3DS
     *
     * @var int
     */
    public const VERIFY_ENROLLMENT = 16;

    protected $tableNameKey = 'payment_operation_types';
}
