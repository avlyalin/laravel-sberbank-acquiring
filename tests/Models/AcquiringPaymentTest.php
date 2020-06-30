<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Models;

use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
use Avlyalin\SberbankAcquiring\Tests\TestCase;

class AcquiringPaymentTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_operations_relation()
    {
        $acquiringPayment = $this->createAcquiringPayment();
        $acquiringPaymentOperation = $this->createAcquiringPaymentOperation(['payment_id' => $acquiringPayment->id]);

        $this->assertTrue($acquiringPayment->operations->contains($acquiringPaymentOperation));
        $this->assertEquals(1, $acquiringPayment->operations->count());
    }

    /**
     * @test
     */
    public function it_has_system_relation()
    {
        $acquiringPayment = $this->createAcquiringPayment();

        $this->assertInstanceOf(DictAcquiringPaymentSystem::class, $acquiringPayment->system);
    }

    /**
     * @test
     */
    public function it_has_status_relation()
    {
        $acquiringPayment = $this->createAcquiringPayment();

        $this->assertInstanceOf(DictAcquiringPaymentStatus::class, $acquiringPayment->status);
    }
}
