<?php

namespace Avlyalin\SberbankAcquiring\Models;

class DictAcquiringPaymentStatus extends BaseModel
{
    /**
     * Зарегистрирован
     *
     * @var string
     */
    public const REGISTERED = 1;

    /**
     * Захолдирован
     *
     * @var string
     */
    public const HELD = 2;

    /**
     * Подтвержден
     *
     * @var string
     */
    public const CONFIRMED = 3;

    /**
     * Отменен
     *
     * @var string
     */
    public const REVERSED = 4;

    /**
     * Оформлен возврат
     *
     * @var string
     */
    public const REFUNDED = 5;

    /**
     * ACS-авторизация
     * @var string
     */
    public const ACS_AUTH = 6;

    /**
     * Ошибка
     *
     * @var string
     */
    public const AUTH_DECLINED = 7;

    /**
     * Системная ошибка при обработке платежа
     *
     * @var string
     */
    public const ERROR = 8;

    protected $tableNameKey = 'dict_payment_statuses';
}
