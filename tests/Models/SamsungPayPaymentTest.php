<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class SamsungPayPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_base_payment_relation()
    {
        $samsungPayPayment = $this->createSamsungPayPayment();

        $this->assertInstanceOf(AcquiringPayment::class, $samsungPayPayment->basePayment);
    }
}
