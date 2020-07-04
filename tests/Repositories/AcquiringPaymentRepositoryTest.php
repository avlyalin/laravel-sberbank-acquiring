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
        $this->repository = $this->app->make(AcquiringPaymentRepository::class);
    }

    /**
     * @test
     */
    public function find_method_returns_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();

        $columns = ['id', 'bank_order_id'];
        $model = $this->repository->find($acquiringPayment->id, $columns);
        $this->assertInstanceOf(AcquiringPayment::class, $model);
        $this->assertEquals($acquiringPayment->id, $model->id);
        $this->assertCount(2, $model->getAttributes());
        $this->assertEquals(['id', 'bank_order_id'], array_keys($model->getAttributes()));
    }

    /**
     * @test
     */
    public function find_or_fail_method_returns_model()
    {
        $acquiringPayment = $this->createAcquiringPayment();

        $columns = ['id', 'status_id', 'system_id'];
        $model = $this->repository->findOrFail($acquiringPayment->id, $columns);
        $this->assertInstanceOf(AcquiringPayment::class, $model);
        $this->assertEquals($acquiringPayment->id, $model->id);
        $this->assertCount(3, $model->getAttributes());
        $this->assertEquals($columns, array_keys($model->getAttributes()));
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
