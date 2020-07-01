<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class ApplePayPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_base_payment_relation()
    {
        $applePayPayment = $this->createApplePayPayment();

        $this->assertInstanceOf(AcquiringPayment::class, $applePayPayment->basePayment);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_sberbank_attributes()
    {
        $params = [
            'orderNumber' => '9mvc5211',
            'description' => 'operation description',
            'language' => 'EN',
            'additionalParameters' => ['param' => 'value'],
            'preAuth' => 'false',
        ];
        $payment = new ApplePayPayment();
        $payment->fillWithSberbankParams($params);

        $this->assertEquals([
            'order_number' => '9mvc5211',
            'description' => 'operation description',
            'language' => 'EN',
            'additional_parameters' => "{\"param\":\"value\"}",
            'pre_auth' => 'false',
        ], $payment->getAttributes());
    }
}
