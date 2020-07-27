<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringSamsungPayPaymentsTable extends Migration
{
    use HasConfig;

    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up()
    {
        $tableName = $this->getTableName('samsung_pay_payments');

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->bigIncrements('id');
            $table->string('order_number', 32)->nullable()->comment('Номер заказа');
            $table->string('description', 512)->nullable()->comment('Описание заказа');
            $table->string('language', 2)->nullable()->comment('Язык в кодировке ISO 639-1');
            $table->string('additional_parameters', 1024)->nullable()->comment('Дополнительные параметры');
            $table->string('pre_auth', 5)->nullable()
                ->comment('Параметр, определяющий необходимость предварительной авторизации');
            $table->string('client_id', 255)->nullable()->comment('Номер (идентификатор) клиента в системе продавца');
            $table->string('ip', 39)->nullable()->comment('IP-адрес покупателя');
            $table->text('payment_token')->comment('Токен, полученный от Samsung Pay');
            $table->string('currency_code', 3)->nullable()->comment('Цифровой код валюты платежа ISO 4217');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down()
    {
        $tableName = $this->getTableName('samsung_pay_payments');
        Schema::dropIfExists($tableName);
    }
}
