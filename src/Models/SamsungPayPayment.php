<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class SamsungPayPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'samsung_pay_payments';

    public $timestamps = false;
}
