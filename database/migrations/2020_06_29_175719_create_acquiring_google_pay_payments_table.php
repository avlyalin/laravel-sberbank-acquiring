<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringGooglePayPaymentsTable extends Migration
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
        $tableName = $this->getTableName('google_pay_payments');
        $basePaymentsTableName = $this->getTableName('payments');

        Schema::create($tableName, function (Blueprint $table) use ($tableName, $basePaymentsTableName) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id')->comment('id в базовой таблице платежей');
            $table->string('order_number', 32)->comment('Номер заказа');
            $table->string('description', 512)->nullable()->comment('Описание заказа');
            $table->string('language', 2)->nullable()->comment('Язык в кодировке ISO 639-1');
            $table->string('additional_parameters', 1024)->nullable()->comment('Дополнительные параметры');
            $table->string('pre_auth', 5)->nullable()
                ->comment('Параметр, определяющий необходимость предварительной авторизации');
            $table->string('client_id', 255)->nullable()->comment('Номер (идентификатор) клиента в системе продавца');
            $table->string('ip', 39)->nullable()->comment('IP-адрес покупателя');
            $table->unsignedBigInteger('amount')->unsigned()->comment('Сумма платежа в минимальных единицах валюты');
            $table->unsignedSmallInteger('currency_code')->nullable()->comment('Цифровой код валюты платежа ISO 4217');
            $table->string('email')->nullable()->comment('Адрес электронной почты покупателя');
            $table->string('phone')->nullable()->comment('Номер телефона покупателя');
            $table->string('return_url', 512)->comment('Адрес для перехода в случае успешной оплаты');
            $table->string('fail_url', 512)->nullable()->comment('Адрес для перехода в случае неуспешной оплаты');

            $table->foreign('payment_id', "{$tableName}_payment_id_foreign")
                ->references('id')
                ->on($basePaymentsTableName)
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
        $tableName = $this->getTableName('google_pay_payments');
        $basePaymentsTableName = $this->getTableName('payments');
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table($tableName, function (Blueprint $table) use ($basePaymentsTableName) {
                $table->dropForeign("{$basePaymentsTableName}_payment_id_foreign");
            });
        }
        Schema::dropIfExists($tableName);
    }
}
