<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class SamsungPayPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'samsung_pay_payments';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'order_number',
        'description',
        'language',
        'additional_parameters',
        'pre_auth',
        'client_id',
        'ip',
    ];

    protected $casts = [
        'additional_parameters' => 'array',
    ];
}
