<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringPaymentOperationsTable extends Migration
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
        $tableName = $this->getTableName('payment_operations');
        $paymentsTableName = $this->getTableName('payments');
        $operationTypesTableName = $this->getTableName('payment_operation_types');

        Schema::create(
            $tableName,
            function (Blueprint $table) use ($tableName, $paymentsTableName, $operationTypesTableName) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('payment_id')->comment('id платежа');
                $table->unsignedBigInteger('user_id')->nullable()->comment('id пользователя-инициатора операции');
                $table->unsignedInteger('type_id')->comment('id типа операции');
                $table->text('request_json')->comment('JSON с данными запроса к банку');
                $table->text('response_json')->nullable()->comment('JSON с ответом от банка');
                $table->timestamps();

                $table->foreign('payment_id', "{$paymentsTableName}_payment_id_foreign")
                    ->references('id')
                    ->on($paymentsTableName)
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

                $table->foreign('type_id', "{$operationTypesTableName}_operation_type_id_foreign")
                    ->references('id')
                    ->on($operationTypesTableName)
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            }
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
        $tableName = $this->getTableName('payment_operations');
        $paymentsTableName = $this->getTableName('payments');
        $operationTypesTableName = $this->getTableName('payment_operation_types');

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table(
                $tableName,
                function (Blueprint $table) use ($paymentsTableName, $operationTypesTableName) {
                    $table->dropForeign("{$paymentsTableName}_payment_id_foreign");
                    $table->dropForeign("{$operationTypesTableName}_operation_type_id_foreign");
                }
            );
        }
        Schema::dropIfExists($tableName);
    }
}
