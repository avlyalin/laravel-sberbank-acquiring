<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentSystem;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Illuminate\Support\Str;

$factory->define(AcquiringPayment::class, function () {
    return [
        'bank_order_id' => Str::random(36),
        'system_id' => AcquiringPaymentSystem::SBERBANK,
        'status_id' => AcquiringPaymentStatus::all()->random()->id,
        'payment_type' => SberbankPayment::class,
        'payment_id' => factory(SberbankPayment::class)->create()->id,
    ];
});

$factory->state(AcquiringPayment::class, 'sberbank', function () {
    return [
        'system_id' => AcquiringPaymentSystem::SBERBANK,
        'payment_type' => SberbankPayment::class,
        'payment_id' => factory(SberbankPayment::class)->create()->id,
    ];
});

$factory->state(AcquiringPayment::class, 'applePay', function () {
    return [
        'system_id' => AcquiringPaymentSystem::APPLE_PAY,
        'payment_type' => ApplePayPayment::class,
        'payment_id' => factory(ApplePayPayment::class)->create()->id,
    ];
});

$factory->state(AcquiringPayment::class, 'samsungPay', function () {
    return [
        'system_id' => AcquiringPaymentSystem::SAMSUNG_PAY,
        'payment_type' => SamsungPayPayment::class,
        'payment_id' => factory(SamsungPayPayment::class)->create()->id,
    ];
});

$factory->state(AcquiringPayment::class, 'googlePay', function () {
    return [
        'system_id' => AcquiringPaymentSystem::GOOGLE_PAY,
        'payment_type' => GooglePayPayment::class,
        'payment_id' => factory(GooglePayPayment::class)->create()->id,
    ];
});
