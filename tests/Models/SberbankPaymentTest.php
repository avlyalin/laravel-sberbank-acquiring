<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Helpers\Currency;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class SberbankPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_base_payment_relation()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $sberbankPayment = $this->createSberbankPayment();
        $acquiringPayment->payment()->associate($sberbankPayment);
        $acquiringPayment->save();

        $this->assertInstanceOf(AcquiringPayment::class, $sberbankPayment->basePayment);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_sberbank_attributes()
    {
        $params = [
            'orderNumber' => '189vxcsm532s',
            'amount' => 7531,
            'currency' => Currency::EUR,
            'returnUrl' => 'http://test.com/api/success',
            'failUrl' => 'http://test.com/api/error',
            'description' => 'operation description',
            'language' => 'EN',
            'clientId' => 'b4vcm251vcxs',
            'pageView' => 'mobile',
            'jsonParams' => ['foo' => 'bar'],
            'sessionTimeoutSecs' => 1000,
            'expirationDate' => '20201231',
            'features' => 'operation features',
        ];
        $payment = new SberbankPayment();
        $payment->fillWithSberbankParams($params);

        $this->assertEquals([
            'order_number' => '189vxcsm532s',
            'amount' => 7531,
            'currency' => Currency::EUR,
            'return_url' => 'http://test.com/api/success',
            'fail_url' => 'http://test.com/api/error',
            'description' => 'operation description',
            'language' => 'EN',
            'client_id' => 'b4vcm251vcxs',
            'page_view' => 'mobile',
            'json_params' => '{"foo":"bar"}',
            'session_timeout_secs' => 1000,
            'expiration_date' => '20201231',
            'features' => 'operation features',
        ], $payment->getAttributes());
    }
}
