<?php

declare(strict_types=1);

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentOperation;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentOperationType;
use Faker\Generator as Faker;

$factory->define(AcquiringPaymentOperation::class, function (Faker $faker) {
    return [
        'user_id' => factory(config('sberbank-acquiring.user.model'))->create()->getKey(),
        'payment_id' => factory(AcquiringPayment::class)->create()->id,
        'type_id' => DictAcquiringPaymentOperationType::all()->random()->id,
        'request_json' => json_encode([$faker->word => $faker->word, $faker->word => $faker->word]),
        'response_json' => json_encode([$faker->word => $faker->word, $faker->word => $faker->word]),
    ];
});
