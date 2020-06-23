<?php

use Avlyalin\SberbankAcquiring\Database\HasTableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringPaymentsTable extends Migration
{
    use HasTableName;

    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up()
    {
        $tableName = $this->getTableName('payments');
        $statusesTableName = $this->getTableName('dict_payment_statuses');

        Schema::create($tableName, function (Blueprint $table) use ($tableName, $statusesTableName) {
            $table->bigIncrements('id');
            $table->unsignedInteger('status_id')->nullable()->comment('id статуса');
            $table->nullableMorphs('payer');
            $table->string('order_number', 32)->nullable()->comment('Номер заказа');
            $table->double('amount')->unsigned()->comment('Сумма платежа в копейках (центах)');
            $table->string('currency', 3)->nullable()->comment('Код валюты платежа ISO 4217');
            $table->string('returnUrl', 512)->comment('Адрес для перехода в случае успешной оплаты');
            $table->string('failUrl', 512)->nullable()->comment('Адрес для перехода в случае неуспешной оплаты');
            $table->string('description', 512)->nullable()->comment('Описание заказа');
            $table->string('language', 2)->nullable()->comment('Язык в кодировке ISO 639-1');
            $table->string('page_view', 20)->nullable()->comment('Страница платежного интерфейса');
            $table->string('merchant_login', 255)->nullable()->comment('Логин дочернего мерчанта');
            $table->string('json_params', 1024)->nullable()->comment('Дополнительные параметры в формате JSON');
            $table->string('session_timeout_secs', 9)->nullable()->comment('Продолжительность заказа в секундах');
            $table->dateTime('expiration_date')->nullable()->comment('Дата и время окончания жизни заказа');
            $table->string('features', 255)->nullable()->comment('Дополнительные параметры операции');
            $table->string('bank_order_id', 36)->nullable()->comment('Номер заказа в платежной системе');
            $table->string('bank_form_url', 512)->nullable()->comment('URL платежной формы');
            $table->timestamps();

            $table->foreign('status_id', "{$tableName}_status_id_foreign")
                ->references('id')
                ->on($statusesTableName)
                ->onUpdate('cascade')
                ->onDelete('restrict');
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
        $tableName = $this->getTableName('payments');
        $statusesTableName = $this->getTableName('payments');
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table($tableName, function (Blueprint $table) use ($statusesTableName) {
                $table->dropForeign("{$statusesTableName}_status_id_foreign");
            });
        }
        Schema::dropIfExists($tableName);
    }
}
