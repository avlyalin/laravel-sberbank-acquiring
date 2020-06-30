<?php

use Avlyalin\SberbankAcquiring\Database\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAcquiringPaymentsTable extends Migration
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
        $tableName = $this->getTableName('payments');
        $statusesTableName = $this->getTableName('dict_payment_statuses');
        $systemsTableName = $this->getTableName('dict_payment_systems');

        Schema::create($tableName, function (Blueprint $table) use ($tableName, $statusesTableName, $systemsTableName) {
            $table->bigIncrements('id');
            $table->string('bank_order_id', 36)->comment('Номер заказа в платежной системе');
            $table->unsignedInteger('status_id')->comment('id статуса заказа');
            $table->unsignedInteger('system_id')->comment('id вида платежной системы');
            $table->timestamps();

            $table->foreign('status_id', "{$tableName}_status_id_foreign")
                ->references('id')
                ->on($statusesTableName)
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('system_id', "{$tableName}_system_id_foreign")
                ->references('id')
                ->on($systemsTableName)
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
