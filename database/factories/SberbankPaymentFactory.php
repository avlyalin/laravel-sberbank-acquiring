<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(SberbankPayment::class, function (Faker $faker) {
    return [
        'payment_id' => factory(AcquiringPayment::class)->create()->id,
        'order_number' => Str::random(32),
        'amount' => $faker->numberBetween(),
        'currency' => $faker->currencyCode,
        'return_url' => $faker->url,
        'fail_url' => $faker->url,
        'bank_form_url' => $faker->url,
    ];
});
