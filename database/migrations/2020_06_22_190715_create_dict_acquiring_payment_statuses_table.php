<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDictAcquiringPaymentStatusesTable extends Migration
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
        $tableName = $this->getTableName('dict_payment_statuses');

        Schema::create($tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bank_id')->nullable()->unique()->comment('id статуса в системе банка');
            $table->date('begin_date')->default('1800-01-01')->comment('Дата начала действия справочного значения');
            $table->date('end_date')->default('9999-12-31')->comment('Дата окончания действия справочного значения');
            $table->string('name')->comment('Имя статуса');
            $table->string('full_name')->comment('Полное имя статуса');
            $table->timestamps();
        });

        DB::table($tableName)->insert(
            [
                ['name' => 'Зарегистрирован', 'full_name' => 'Платеж зарегистрирован, но не оплачен', 'bank_id' => 0],
                ['name' => 'Захолдирован', 'full_name' => 'Предавторизованная сумма захолдирована', 'bank_id' => 1],
                ['name' => 'Подтвержден', 'full_name' => 'Проведена полная авторизация суммы', 'bank_id' => 2],
                ['name' => 'Отменен', 'full_name' => 'Авторизация отменена', 'bank_id' => 3],
                ['name' => 'Оформлен возврат', 'full_name' => 'По транзакции была проведена операция возврата', 'bank_id' => 4],
                ['name' => 'ACS-авторизация', 'full_name' => 'Инициирована авторизация через ACS банка-эмитента', 'bank_id' => 5],
                ['name' => 'Отклонен', 'full_name' => 'Авторизация отклонена', 'bank_id' => 6],
                ['name' => 'Ошибка', 'full_name' => 'Системная ошибка при обработке платежа', 'bank_id' => null],
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down()
    {
        $tableName = $this->getTableName('dict_payment_statuses');
        Schema::dropIfExists($tableName);
    }
}
