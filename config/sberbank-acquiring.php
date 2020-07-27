<?php

return [
    /**
     * Авторизационные данные
     */
    'auth' => [
        'userName' => env('SBERBANK_USERNAME', ''),
        'password' => env('SBERBANK_PASSWORD', ''),
        'token' => env('SBERBANK_TOKEN', ''),
    ],

    /**
     * Логин продавца в платёжном шлюзе
     */
    'merchant_login' => env('SBERBANK_MERCHANT_LOGIN', ''),

    /**
     * Адрес сервера Сбербанка
     */
    'base_uri' => env('SBERBANK_URI', 'https://3dsec.sberbank.ru'),

    /**
     * Дополнительные параметры
     */
    'params' => [
        /**
         * URL для перехода в случае успешной регистрации заказа
         */
        'return_url' => env('SBERBANK_RETURN_URL', ''),

        /**
         * URL для перехода в случае неуспешной регистрации заказа
         */
        'fail_url' => env('SBERBANK_FAIL_URL', ''),
    ],

    /**
     * Настройки модели пользователя
     */
    'user' => [
        'model' => \App\User::class,
        'table' => 'users',
        'primary_key' => 'id',
    ],

    /**
     * Названия таблиц ('ключ' => 'название')
     */
    'tables' => [
        /**
         * Базовая таблица платежей
         */
        'payments' => 'acquiring_payments',

        /**
         * Операции по платежам
         */
        'payment_operations' => 'acquiring_payment_operations',

        /**
         * Платежи напрямую через систему Сбербанка
         */
        'sberbank_payments' => 'acquiring_sberbank_payments',

        /**
         * Платежи через Apple Pay
         */
        'apple_pay_payments' => 'acquiring_apple_pay_payments',

        /**
         * Платежи через Samsung Pay
         */
        'samsung_pay_payments' => 'acquiring_samsung_pay_payments',

        /**
         * Платежи через Google Pay
         */
        'google_pay_payments' => 'acquiring_google_pay_payments',

        /**
         * Статусы платежей
         */
        'payment_statuses' => 'acquiring_payment_statuses',

        /**
         * Типы операций
         */
        'payment_operation_types' => 'acquiring_payment_operation_types',

        /**
         * Типы платежных систем
         */
        'payment_systems' => 'acquiring_payment_systems',
    ],
];
