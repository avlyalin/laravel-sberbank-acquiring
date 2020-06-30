<?php

use Avlyalin\SberbankAcquiring\Database\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDictAcquiringPaymentSystemsTable extends Migration
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
        $tableName = $this->getTableName('dict_payment_systems');

        Schema::create($tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->date('begin_date')->default('1800-01-01')->comment('Дата начала действия справочного значения');
            $table->date('end_date')->default('9999-12-31')->comment('Дата окончания действия справочного значения');
            $table->string('name')->comment('Имя системы');
            $table->string('full_name')->comment('Полное имя системы');
            $table->timestamps();
        });

        DB::table($tableName)->insert(
            [
                ['name' => 'Сбербанк', 'full_name' => 'Платеж через систему Сбербанка'],
                ['name' => 'Apple Pay', 'full_name' => 'Платеж через Apple Pay'],
                ['name' => 'Samsung Pay', 'full_name' => 'Платеж через Samsung Pay'],
                ['name' => 'Google Pay', 'full_name' => 'Платеж через Google Pay'],
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
        $tableName = $this->getTableName('dict_payment_systems');
        Schema::dropIfExists($tableName);
    }
}
