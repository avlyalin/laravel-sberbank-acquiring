<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class GooglePayPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'google_pay_payments';

    public $timestamps = false;
}
