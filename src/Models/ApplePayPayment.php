<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class ApplePayPayment extends BasePaymentModel
{
    protected $tableNameKey = 'apple_pay_payments';

    public $timestamps = false;

    protected $hidden = [
        'payment_token',
    ];

    protected $fillable = [
        'order_number',
        'description',
        'language',
        'additional_parameters',
        'pre_auth',
    ];

    protected $casts = [
        'additional_parameters' => 'array',
    ];

    protected $acquiringParamsMap = [
        'orderNumber' => 'order_number',
        'description' => 'description',
        'language' => 'language',
        'additionalParameters' => 'additional_parameters',
        'preAuth' => 'pre_auth',
    ];
}
