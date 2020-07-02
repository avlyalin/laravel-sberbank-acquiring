<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(GooglePayPayment::class, function (Faker $faker) {
    return [
        'order_number' => Str::random(36),
        'description' => $faker->sentence,
        'language' => $faker->languageCode,
        'additional_parameters' => json_encode([$faker->word => $faker->word, $faker->word => $faker->word]),
        'pre_auth' => $faker->randomElement(['true', 'false']),
        'client_id' => Str::random(30),
        'ip' => $faker->ipv6,
        'amount' => $faker->numberBetween(),
        'currency_code' => $faker->currencyCode,
        'email' => $faker->email,
        'phone' => $faker->phoneNumber,
        'return_url' => $faker->url,
        'fail_url' => $faker->url,
    ];
});
