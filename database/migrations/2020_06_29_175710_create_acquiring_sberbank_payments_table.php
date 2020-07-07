<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringSberbankPaymentsTable extends Migration
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
        $tableName = $this->getTableName('sberbank_payments');

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->bigIncrements('id');
            $table->string('order_number', 32)->nullable()->comment('Номер заказа');
            $table->unsignedBigInteger('amount')->unsigned()->comment('Сумма платежа в минимальных единицах валюты');
            $table->unsignedSmallInteger('currency')->nullable()->comment('Код валюты платежа ISO 4217');
            $table->string('return_url', 512)->comment('Адрес для перехода в случае успешной оплаты');
            $table->string('fail_url', 512)->nullable()->comment('Адрес для перехода в случае неуспешной оплаты');
            $table->string('description', 512)->nullable()->comment('Описание заказа');
            $table->string('language', 2)->nullable()->comment('Язык в кодировке ISO 639-1');
            $table->string('client_id', 255)->nullable()->comment('Номер (идентификатор) клиента в системе продавца');
            $table->string('page_view', 20)->nullable()->comment('Страница платежного интерфейса');
            $table->string('json_params', 1024)->nullable()->comment('Дополнительные параметры в формате JSON');
            $table->string('session_timeout_secs', 9)->nullable()->comment('Продолжительность заказа в секундах');
            $table->string('expiration_date')->nullable()->comment('Дата и время окончания жизни заказа');
            $table->string('features', 255)->nullable()->comment('Дополнительные параметры операции');
            $table->string('bank_form_url', 512)->nullable()->comment('URL платежной формы');
            $table->timestamps();
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
        $tableName = $this->getTableName('sberbank_payments');
        Schema::dropIfExists($tableName);
    }
}
