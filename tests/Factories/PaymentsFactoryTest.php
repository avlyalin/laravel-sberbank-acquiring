<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Factories;

use Avlyalin\SberbankAcquiring\Factories\PaymentsFactory;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class PaymentsFactoryTest extends TestCase
{
    /**
     * @var PaymentsFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PaymentsFactory();
    }

    /**
     * @test
     */
    public function it_creates_new_sberbank_payment_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $sberbankPayment = $this->factory->createSberbankPayment([
            'payment_id' => $acquiringPayment->id,
            'amount' => 100,
            'return_url' => 'http://test-url.com',
        ]);

        $this->assertInstanceOf(SberbankPayment::class, $sberbankPayment);
        $this->assertEquals($sberbankPayment->payment_id, $acquiringPayment->id);
    }

    /**
     * @test
     */
    public function it_creates_new_apple_pay_payment_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $applePayPayment = $this->factory->createApplePayPayment([
            'payment_id' => $acquiringPayment->id,
            'order_number' => '5nvc8-41ncx4210',
        ]);

        $this->assertInstanceOf(ApplePayPayment::class, $applePayPayment);
        $this->assertEquals($applePayPayment->payment_id, $acquiringPayment->id);
        $this->assertEquals($applePayPayment->order_number, '5nvc8-41ncx4210');
    }

    /**
     * @test
     */
    public function it_creates_new_samsung_pay_payment_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $samsungPayPayment = $this->factory->createSamsungPayPayment([
            'payment_id' => $acquiringPayment->id,
            'order_number' => '9m14-cn532=vc',
        ]);

        $this->assertInstanceOf(SamsungPayPayment::class, $samsungPayPayment);
        $this->assertEquals($samsungPayPayment->payment_id, $acquiringPayment->id);
        $this->assertEquals($samsungPayPayment->order_number, '9m14-cn532=vc');
    }

    /**
     * @test
     */
    public function it_creates_new_google_pay_payment_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $googlePayPayment = $this->factory->createGooglePayPayment([
            'payment_id' => $acquiringPayment->id,
            'order_number' => '1vmc94-421mnvx',
            'amount' => 1100,
            'return_url' => 'http://test-url.com/google-pay',
        ]);

        $this->assertInstanceOf(GooglePayPayment::class, $googlePayPayment);
        $this->assertEquals($googlePayPayment->payment_id, $acquiringPayment->id);
        $this->assertEquals($googlePayPayment->order_number, '1vmc94-421mnvx');
        $this->assertEquals($googlePayPayment->amount, 1100);
        $this->assertEquals($googlePayPayment->return_url, 'http://test-url.com/google-pay');
    }
}
