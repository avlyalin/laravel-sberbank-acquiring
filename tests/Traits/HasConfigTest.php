<?php

namespace Avlyalin\SberbankAcquiring\Tests\Traits;

use Avlyalin\SberbankAcquiring\Tests\TestCase;
use Avlyalin\SberbankAcquiring\Traits\HasConfig;
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

        $mock = $this->getMockForHasConfigTrait();

        $this->assertEquals($expectedTableName, $mock->getTableName('payment'));
    }

    /**
     * @test
     */
    public function should_throw_exception_for_bad_table_name_key()
    {
        $expectedTableName = 'some_payments_table';
        Config::set('sberbank-acquiring.table_names.bad_key', $expectedTableName);

        $mock = $this->getMockForHasConfigTrait();

        $this->expectException(\Exception::class);
        $mock->getTableName('payment');
    }

    /**
     * @test
     */
    public function should_return_auth_params()
    {
        $authParams = [
            'userName' => 'some_test_userName',
            'password' => 'some_test_password',
            'token' => 'some_test_token',
        ];
        Config::set('sberbank-acquiring.auth', $authParams);

        $mock = $this->getMockForHasConfigTrait();

        $this->assertEquals($authParams, $mock->getConfigAuthParams());
    }

    /**
     * @test
     */
    public function should_return_merchant_login_param()
    {
        $merchantLogin = 'some_test_merchant_login';
        Config::set('sberbank-acquiring.merchant_login', $merchantLogin);

        $mock = $this->getMockForHasConfigTrait();

        $this->assertEquals($merchantLogin, $mock->getConfigMerchantLoginParam());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|HasConfig
     */
    private function getMockForHasConfigTrait()
    {
        return $this->getMockForTrait(HasConfig::class);
    }
}
