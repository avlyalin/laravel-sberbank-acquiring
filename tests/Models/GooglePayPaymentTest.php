<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Helpers\Currency;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class GooglePayPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_base_payment_relation()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $googlePayPayment = $this->createSberbankPayment();
        $acquiringPayment->payment()->associate($googlePayPayment);
        $acquiringPayment->save();


        $this->assertInstanceOf(AcquiringPayment::class, $googlePayPayment->basePayment);
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
            'amount' => 20051,
            'currencyCode' => Currency::USD,
            'email' => 'test@test.test',
            'phone' => '+7999999999',
            'returnUrl' => 'http://test.com/api/success',
            'failUrl' => 'http://test.com/api/error',
        ];
        $payment = new GooglePayPayment();
        $payment->fillWithSberbankParams($params);

        $this->assertEquals([
            'order_number' => 'uvc124v',
            'description' => 'operation description',
            'language' => 'EN',
            'additional_parameters' => "{\"param\":\"value\"}",
            'pre_auth' => 'false',
            'client_id' => '1vc-21bvd21',
            'ip' => '10.10.10.10',
            'amount' => 20051,
            'currency_code' => Currency::USD,
            'email' => 'test@test.test',
            'phone' => '+7999999999',
            'return_url' => 'http://test.com/api/success',
            'fail_url' => 'http://test.com/api/error',
        ], $payment->getAttributes());
    }

    /**
     * @test
     */
    public function it_has_not_fillable_payment_token()
    {
        $payment = new GooglePayPayment();
        $payment->fill(['payment_token' => 'some-string']);

        $this->assertEmpty($payment->payment_token);
    }

    /**
     * @test
     */
    public function it_has_hidden_payment_token()
    {
        $payment = new GooglePayPayment();
        $payment->payment_token = 'some-string';

        $this->assertNotEmpty($payment->payment_token);
        $this->assertArrayNotHasKey('payment_token', $payment->toArray());
    }

    /**
     * @test
     */
    public function it_can_get_and_set_payment_token()
    {
        $payment = new GooglePayPayment();
        $token = $payment->getPaymentToken();
        $this->assertNull($token);

        $token = 'token-string';
        $payment->setPaymentToken($token);

        $this->assertEquals($token, $payment->getPaymentToken());
    }
}
