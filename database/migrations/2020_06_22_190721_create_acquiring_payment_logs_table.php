<?php

use Avlyalin\SberbankAcquiring\Database\HasTableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringPaymentLogsTable extends Migration
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
        $tableName = $this->getTableName('payment_logs');
        $statusesTableName = $this->getTableName('dict_payment_statuses');
        $paymentsTableName = $this->getTableName('payments');

        Schema::create(
            $tableName,
            function (Blueprint $table) use ($tableName, $paymentsTableName, $statusesTableName) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('payment_id')->comment('id платежа');
                $table->nullableMorphs('user');
                $table->unsignedInteger('payment_status_old_id')->nullable()->comment('id статуса платежа до изменения');
                $table->unsignedInteger('payment_status_new_id')->nullable()->comment('id статуса платежа после изменения');
                $table->string('operation_name', 255)->comment('Название операции');
                $table->text('changes_json')->comment('JSON с изменившимися аттрибутами модели платежа и их новыми значениями');
                $table->text('request_json')->comment('JSON с данными запроса к банку');
                $table->text('response_json')->comment('JSON с ответом от банка');
                $table->timestamps();

                $table->foreign('payment_id', "{$paymentsTableName}_payment_id_foreign")
                    ->references($paymentsTableName)
                    ->on('id')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

                $table->foreign('payment_status_old_id', "{$statusesTableName}_payment_status_old_id_foreign")
                    ->references($statusesTableName)
                    ->on('id')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');

                $table->foreign('payment_status_new_id', "{$statusesTableName}_payment_status_new_id_foreign")
                    ->references($statusesTableName)
                    ->on('id')
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
        $tableName = $this->getTableName('payment_logs');
        $statusesTableName = $this->getTableName('dict_payment_statuses');
        $paymentsTableName = $this->getTableName('payments');

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table($tableName, function (Blueprint $table) use ($statusesTableName, $paymentsTableName) {
                $table->dropForeign("{$paymentsTableName}_payment_id_foreign");
                $table->dropForeign("{$statusesTableName}_payment_status_old_id_foreign");
                $table->dropForeign("{$statusesTableName}_payment_status_new_id_foreign");
            });
        }
        Schema::dropIfExists($tableName);
    }
}
