<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class GooglePayPayment extends BasePaymentModel
{
    protected $tableNameKey = 'google_pay_payments';

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
        'amount',
        'currency_code',
        'email',
        'phone',
        'return_url',
        'fail_url',
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
        'clientId' => 'client_id',
        'ip' => 'ip',
        'amount' => 'amount',
        'currencyCode' => 'currency_code',
        'email' => 'email',
        'phone' => 'phone',
        'returnUrl' => 'return_url',
        'failUrl' => 'fail_url',
    ];
}
