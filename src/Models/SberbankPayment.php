<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

class SberbankPayment extends BaseModel implements PaymentInterface
{
    use HasBasePayment;

    protected $tableNameKey = 'sberbank_payments';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'order_number',
        'amount',
        'currency',
        'return_url',
        'fail_url',
        'description',
        'client_id',
        'language',
        'page_view',
        'merchant_login',
        'json_params',
        'session_timeout_secs',
        'expiration_date',
        'features',
        'bank_form_url',
    ];

    protected $casts = [
        'json_params' => 'array',
    ];
}
