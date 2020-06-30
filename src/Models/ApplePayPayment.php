<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class ApplePayPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'apple_pay_payments';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'order_number',
        'description',
        'language',
        'additional_parameters',
        'pre_auth',
    ];

    protected $casts = [
        'additional_parameters' => 'array',
    ];
}
