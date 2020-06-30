<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
use Illuminate\Support\Str;

$factory->define(AcquiringPayment::class, function () {
    return [
        'bank_order_id' => Str::random(36),
        'system_id' => DictAcquiringPaymentSystem::all()->random()->id,
        'status_id' => DictAcquiringPaymentStatus::all()->random()->id,
    ];
});
