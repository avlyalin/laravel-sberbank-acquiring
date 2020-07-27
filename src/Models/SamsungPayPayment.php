<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

use Avlyalin\SberbankAcquiring\Interfaces\HasPaymentToken as HasPaymentTokenInterface;
use Avlyalin\SberbankAcquiring\Traits\HasPaymentToken;

class SamsungPayPayment extends BasePaymentModel implements HasPaymentTokenInterface
{
    use HasPaymentToken;

    protected $tableNameKey = 'samsung_pay_payments';

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
        'client_id',
        'ip',
        'currency_code',
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
        'currencyCode' => 'currency_code',
    ];
}
