<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class SamsungPayPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_base_payment_relation()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $samsungPayPayment = $this->createSberbankPayment();
        $acquiringPayment->payment()->associate($samsungPayPayment);
        $acquiringPayment->save();

        $this->assertInstanceOf(AcquiringPayment::class, $samsungPayPayment->basePayment);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_sberbank_attributes()
    {
        $params = [
            'orderNumber' => 'uvc124v',
            'description' => 'operation description',
            'language' => 'EN',
            'additionalParameters' => ['param' => 'value'],
            'preAuth' => 'false',
            'clientId' => '1vc-21bvd21',
            'ip' => '10.10.10.10',
        ];
        $payment = new SamsungPayPayment();
        $payment->fillWithSberbankParams($params);

        $this->assertEquals([
            'order_number' => 'uvc124v',
            'description' => 'operation description',
            'language' => 'EN',
            'additional_parameters' => "{\"param\":\"value\"}",
            'pre_auth' => 'false',
            'client_id' => '1vc-21bvd21',
            'ip' => '10.10.10.10',
        ], $payment->getAttributes());
    }

    /**
     * @test
     */
    public function it_has_not_fillable_payment_token()
    {
        $payment = new SamsungPayPayment();
        $payment->fill(['payment_token' => 'some-string']);

        $this->assertEmpty($payment->payment_token);
    }

    /**
     * @test
     */
    public function it_has_hidden_payment_token()
    {
        $payment = new SamsungPayPayment();
        $payment->payment_token = 'some-string';

        $this->assertNotEmpty($payment->payment_token);
        $this->assertArrayNotHasKey('payment_token', $payment->toArray());
    }

    /**
     * @test
     */
    public function it_can_get_and_set_payment_token()
    {
        $payment = new SamsungPayPayment();
        $token = $payment->getPaymentToken();
        $this->assertNull($token);

        $token = 'token-string';
        $payment->setPaymentToken($token);

        $this->assertEquals($token, $payment->getPaymentToken());
    }
}
