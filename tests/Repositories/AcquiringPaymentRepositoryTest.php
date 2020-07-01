<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Repositories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentRepository;
use Avlyalin\SberbankAcquiring\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcquiringPaymentRepositoryTest extends TestCase
{
    /**
     * @var AcquiringPaymentRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AcquiringPaymentRepository(new AcquiringPayment());
    }

    /**
     * @test
     */
    public function find_method_returns_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();

        $model = $this->repository->find($acquiringPayment->id);
        $this->assertInstanceOf(AcquiringPayment::class, $model);
        $this->assertEquals($acquiringPayment->id, $model->id);
    }

    /**
     * @test
     */
    public function find_or_fail_method_returns_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();

        $model = $this->repository->findOrFail($acquiringPayment->id);
        $this->assertInstanceOf(AcquiringPayment::class, $model);
        $this->assertEquals($acquiringPayment->id, $model->id);
    }

    /**
     * @test
     */
    public function find_or_fail_method_throws_exception_when_cannot_find_model()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->repository->findOrFail(1001231);
    }
}
