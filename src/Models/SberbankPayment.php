<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class SberbankPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'sberbank_payments';

    public $timestamps = false;
}
