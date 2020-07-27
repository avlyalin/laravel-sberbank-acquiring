<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(SamsungPayPayment::class, function (Faker $faker) {
    return [
        'order_number' => Str::random(36),
        'description' => $faker->sentence,
        'language' => $faker->languageCode,
        'additional_parameters' => json_encode([$faker->word => $faker->word, $faker->word => $faker->word]),
        'pre_auth' => $faker->randomElement(['true', 'false']),
        'client_id' => Str::random(30),
        'ip' => $faker->ipv6,
        'payment_token' => Str::random(100),
        'currency_code' => (string)$faker->numberBetween(100, 999),
    ];
});
