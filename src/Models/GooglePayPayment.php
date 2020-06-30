<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class GooglePayPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

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
}
