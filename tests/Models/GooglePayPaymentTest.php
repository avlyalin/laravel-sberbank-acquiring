<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
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
}
