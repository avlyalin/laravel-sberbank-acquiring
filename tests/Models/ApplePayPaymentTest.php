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
        $acquiringPayment = $this->createAcquiringPayment();
        $applePayPayment = $this->createApplePayPayment();
        $acquiringPayment->payment()->associate($applePayPayment);
        $acquiringPayment->save();

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

    /**
     * @test
     */
    public function it_has_not_fillable_payment_token()
    {
        $payment = new ApplePayPayment();
        $payment->fill(['payment_token' => 'some-string']);

        $this->assertEmpty($payment->payment_token);
    }

    /**
     * @test
     */
    public function it_has_hidden_payment_token()
    {
        $payment = new ApplePayPayment();
        $payment->payment_token = 'some-string';

        $this->assertNotEmpty($payment->payment_token);
        $this->assertArrayNotHasKey('payment_token', $payment->toArray());
    }

    /**
     * @test
     */
    public function it_can_get_and_set_payment_token()
    {
        $payment = new ApplePayPayment();
        $token = $payment->getPaymentToken();
        $this->assertNull($token);

        $token = 'token-string';
        $payment->setPaymentToken($token);

        $this->assertEquals($token, $payment->getPaymentToken());
    }
}
