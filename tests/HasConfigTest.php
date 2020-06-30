<?php

namespace Avlyalin\SberbankAcquiring\Tests;

use Avlyalin\SberbankAcquiring\Database\HasConfig;
use Illuminate\Support\Facades\Config;

class HasConfigTest extends TestCase
{
    /**
     * @test
     */
    public function should_return_table_name_for_correct_table_name_key()
    {
        $expectedTableName = 'some_payments_table';
        Config::set('sberbank-acquiring.table_names.payment', $expectedTableName);

        $mock = $this->getMockForTrait(HasConfig::class);

        $this->assertEquals($expectedTableName, $mock->getTableName('payment'));
    }

    /**
     * @test
     */
    public function should_throw_exception_for_bad_table_name_key()
    {
        $expectedTableName = 'some_payments_table';
        Config::set('sberbank-acquiring.table_names.bad_key', $expectedTableName);

        $mock = $this->getMockForTrait(HasConfig::class);

        $this->expectException(\Exception::class);
        $mock->getTableName('payment');
    }
}
