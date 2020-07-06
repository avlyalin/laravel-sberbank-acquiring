<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\BasePaymentModel;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class BasePaymentModelTest extends TestCase
{
    /**
     * @test
     */
    public function fill_with_sberbank_params_throws_exception_when_gets_unknown_param()
    {
        $this->expectException(\InvalidArgumentException::class);

        $model = $this->getMockForAbstractClass(BasePaymentModel::class, [], '', false);
        $this->setProtectedProperty($model, 'acquiringParamsMap', ['sberParam' => 'sber_param']);

        $model->fillWithSberbankParams(['unknownParam' => 'value']);
    }

    /**
     * @test
     */
    public function fill_with_sberbank_params_fills_fillable_attributes()
    {
        $model = $this->getMockForAbstractClass(BasePaymentModel::class, [], '', false);
        $model->fillable(['sber_param_2']);
        $this->setProtectedProperty($model, 'acquiringParamsMap', ['sberParam' => 'sber_param', 'sberParam2' => 'sber_param_2']);

        $model->fillWithSberbankParams(['sberParam' => 'param', 'sberParam2' => 'param2']);

        $this->assertEquals(['sber_param_2' => 'param2'], $model->getAttributes());
    }
}
