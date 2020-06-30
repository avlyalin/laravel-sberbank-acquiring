<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(ApplePayPayment::class, function (Faker $faker) {
    return [
        'payment_id' => factory(AcquiringPayment::class)->create()->id,
        'order_number' => Str::random(36),
        'description' => $faker->sentence,
        'language' => $faker->languageCode,
        'additional_parameters' => json_encode([$faker->word => $faker->word, $faker->word => $faker->word]),
        'pre_auth' => $faker->randomElement(['true', 'false']),
    ];
});
