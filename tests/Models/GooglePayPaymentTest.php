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
        $googlePayPayment = $this->createGooglePayPayment();

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
}
