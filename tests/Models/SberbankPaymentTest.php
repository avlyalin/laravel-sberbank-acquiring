<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class SberbankPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_base_payment_relation()
    {
        $sberbankPayment = $this->createSberbankPayment();

        $this->assertInstanceOf(AcquiringPayment::class, $sberbankPayment->basePayment);
    }
}
