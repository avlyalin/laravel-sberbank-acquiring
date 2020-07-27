<?php

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAcquiringPaymentSystemsTable extends Migration
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
        $tableName = $this->getTableName('payment_systems');

        Schema::create($tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('Имя системы');
            $table->string('full_name')->comment('Полное имя системы');
            $table->boolean('is_active')->default(1)->comment('Флаг действия справочного значения');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
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
        $tableName = $this->getTableName('payment_systems');
        Schema::dropIfExists($tableName);
    }
}
