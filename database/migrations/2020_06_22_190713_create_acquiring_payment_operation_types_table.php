<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAcquiringPaymentOperationTypesTable extends Migration
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
        $tableName = $this->getTableName('payment_operation_types');

        Schema::create($tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('Название операции');
            $table->string('full_name')->comment('Полное название операции');
            $table->boolean('is_active')->default(1)->comment('Флаг действия справочного значения');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        DB::table($tableName)->insert(
            [
                ['name' => 'Регистрация заказа', 'full_name' => 'Регистрация заказа'],
                ['name' => 'Регистрация заказа с предавторизацией', 'full_name' => 'Регистрация заказа с предавторизацией'],
                ['name' => 'Запрос завершения оплаты заказа', 'full_name' => 'Запрос завершения оплаты заказа'],
                ['name' => 'Запрос отмены оплаты заказа', 'full_name' => 'Запрос отмены оплаты заказа'],
                ['name' => 'Запрос возврата средств оплаты заказа', 'full_name' => 'Запрос возврата средств оплаты заказа'],
                ['name' => 'Расширенный запрос состояния заказа', 'full_name' => 'Расширенный запрос состояния заказа'],
                ['name' => 'Запрос оплаты через Apple Pay', 'full_name' => 'Запрос оплаты через Apple Pay'],
                ['name' => 'Запрос оплаты через Samsung Pay', 'full_name' => 'Запрос оплаты через Samsung Pay'],
                ['name' => 'Запрос оплаты через Google Pay', 'full_name' => 'Запрос оплаты через Google Pay'],
                ['name' => 'Запрос сведений о кассовом чеке', 'full_name' => 'Запрос сведений о кассовом чеке'],
                ['name' => 'Запрос активации связки', 'full_name' => 'Запрос активации связки'],
                ['name' => 'Запрос деактивации связки', 'full_name' => 'Запрос деактивации связки'],
                ['name' => 'Запрос списка всех связок клиента', 'full_name' => 'Запрос списка всех связок клиента'],
                ['name' => 'Запрос списка связок определённой банковской карты', 'full_name' => 'Запрос списка связок определённой банковской карты'],
                ['name' => 'Запрос изменения срока действия связки', 'full_name' => 'Запрос изменения срока действия связки'],
                ['name' => 'Запрос проверки вовлечённости карты в 3DS', 'full_name' => 'Запрос проверки вовлечённости карты в 3DS'],
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
        $tableName = $this->getTableName('payment_operation_types');
        Schema::dropIfExists($tableName);
    }
}
