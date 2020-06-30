<?php

return [
    'user' => [
        'model' => \App\User::class,
        'table' => 'users',
        'primary_key' => 'id'
    ],

    'table_names' => [
        'payments' => 'acquiring_payments',
        'payment_operations' => 'acquiring_payment_operations',
        'dict_payment_statuses' => 'dict_acquiring_payment_statuses',
        'dict_payment_operation_types' => 'dict_acquiring_payment_operation_types',
        'dict_payment_systems' => 'dict_acquiring_payment_systems',
        'sberbank_payments' => 'acquiring_sberbank_payments',
        'apple_pay_payments' => 'acquiring_apple_pay_payments',
        'samsung_pay_payments' => 'acquiring_samsung_pay_payments',
        'google_pay_payments' => 'acquiring_google_pay_payments',
    ]
];
