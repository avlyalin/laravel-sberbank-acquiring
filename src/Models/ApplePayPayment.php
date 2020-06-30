<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class ApplePayPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'apple_pay_payments';

    public $timestamps = false;
}
