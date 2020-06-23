<?php

return [
    'user' => [
        'model' => \App\User::class,
        'table' => 'users',
        'primary_key' => 'id'
    ],

    'table_names' => [
        'payments' => 'acquiring_payments',
        'payment_logs' => 'acquiring_payment_logs',
        'dict_payment_statuses' => 'dict_acquiring_payment_statuses',
    ]
];
