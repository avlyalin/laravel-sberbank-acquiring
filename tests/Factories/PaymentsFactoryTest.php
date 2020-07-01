<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Factories;

use Avlyalin\SberbankAcquiring\Factories\PaymentsFactory;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentOperation;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentOperationType;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
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
    public function it_creates_new_acquiring_payment_model()
    {
        $acquiringPayment = $this->factory->createAcquiringPayment([
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
            'status_id' => DictAcquiringPaymentStatus::REGISTERED,
        ]);

        $this->assertInstanceOf(AcquiringPayment::class, $acquiringPayment);
        $this->assertEquals($acquiringPayment->system_id, DictAcquiringPaymentSystem::SBERBANK);
        $this->assertEquals($acquiringPayment->status_id, DictAcquiringPaymentStatus::REGISTERED);
    }

    /**
     * @test
     */
    public function it_creates_new_sberbank_payment_model()
    {
        $sberbankPayment = $this->factory->createSberbankPayment([
            'payment_id' => 42,
            'amount' => 100,
            'return_url' => 'http://test-url.com',
        ]);

        $this->assertInstanceOf(SberbankPayment::class, $sberbankPayment);
        $this->assertEquals($sberbankPayment->payment_id, 42);
    }

    /**
     * @test
     */
    public function it_creates_new_apple_pay_payment_model()
    {
        $applePayPayment = $this->factory->createApplePayPayment([
            'payment_id' => 100,
            'order_number' => '5nvc8-41ncx4210',
        ]);

        $this->assertInstanceOf(ApplePayPayment::class, $applePayPayment);
        $this->assertEquals($applePayPayment->payment_id, 100);
        $this->assertEquals($applePayPayment->order_number, '5nvc8-41ncx4210');
    }

    /**
     * @test
     */
    public function it_creates_new_samsung_pay_payment_model()
    {
        $samsungPayPayment = $this->factory->createSamsungPayPayment([
            'payment_id' => 10,
            'order_number' => '9m14-cn532=vc',
        ]);

        $this->assertInstanceOf(SamsungPayPayment::class, $samsungPayPayment);
        $this->assertEquals($samsungPayPayment->payment_id, 10);
        $this->assertEquals($samsungPayPayment->order_number, '9m14-cn532=vc');
    }

    /**
     * @test
     */
    public function it_creates_new_google_pay_payment_model()
    {
        $googlePayPayment = $this->factory->createGooglePayPayment([
            'payment_id' => 123,
            'order_number' => '1vmc94-421mnvx',
            'amount' => 1100,
            'return_url' => 'http://test-url.com/google-pay',
        ]);

        $this->assertInstanceOf(GooglePayPayment::class, $googlePayPayment);
        $this->assertEquals($googlePayPayment->payment_id, 123);
        $this->assertEquals($googlePayPayment->order_number, '1vmc94-421mnvx');
        $this->assertEquals($googlePayPayment->amount, 1100);
        $this->assertEquals($googlePayPayment->return_url, 'http://test-url.com/google-pay');
    }

    /**
     * @test
     */
    public function it_creates_acquiring_payment_operation_model()
    {
        $operation = $this->factory->createPaymentOperation([
            'payment_id' => 144,
            'user_id' => 1341,
            'type_id' => DictAcquiringPaymentOperationType::REGISTER,
        ]);

        $this->assertInstanceOf(AcquiringPaymentOperation::class, $operation);
        $this->assertEquals($operation->payment_id, 144);
        $this->assertEquals($operation->user_id, 1341);
        $this->assertEquals($operation->type_id, DictAcquiringPaymentOperationType::REGISTER);
    }
}
