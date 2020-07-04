<?php

namespace Avlyalin\SberbankAcquiring\Models;

class DictAcquiringPaymentStatus extends BaseModel
{
    /**
     * Новый
     *
     * @var string
     */
    public const NEW = 1;

    /**
     * Зарегистрирован
     *
     * @var string
     */
    public const REGISTERED = 2;

    /**
     * Захолдирован
     *
     * @var string
     */
    public const HELD = 3;

    /**
     * Подтвержден
     *
     * @var string
     */
    public const CONFIRMED = 4;

    /**
     * Отменен
     *
     * @var string
     */
    public const REVERSED = 5;

    /**
     * Оформлен возврат
     *
     * @var string
     */
    public const REFUNDED = 6;

    /**
     * ACS-авторизация
     * @var string
     */
    public const ACS_AUTH = 7;

    /**
     * Ошибка
     *
     * @var string
     */
    public const AUTH_DECLINED = 8;

    /**
     * Системная ошибка при обработке платежа
     *
     * @var string
     */
    public const ERROR = 9;

    protected $tableNameKey = 'dict_payment_statuses';
}
