<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class AcquiringPaymentSystem extends BaseModel
{
    /**
     * Сбербанк
     *
     * @var
     */
    public const SBERBANK = 1;

    /**
     * Apple pay
     *
     * @var
     */
    public const APPLE_PAY = 2;

    /**
     * Samsung pay
     *
     * @var
     */
    public const SAMSUNG_PAY = 3;

    /**
     * Google pay
     *
     * @var
     */
    public const GOOGLE_PAY = 4;

    protected $tableNameKey = 'payment_systems';
}
