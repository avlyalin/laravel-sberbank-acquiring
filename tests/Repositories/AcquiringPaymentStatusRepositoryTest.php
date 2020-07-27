<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Repositories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentStatusRepository;
use Avlyalin\SberbankAcquiring\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcquiringPaymentStatusRepositoryTest extends TestCase
{
    /**
     * @var AcquiringPaymentStatusRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(AcquiringPaymentStatusRepository::class);
    }

    /**
     * @test
     */
    public function find_method_returns_model()
    {
        $columns = ['id', 'full_name'];
        $model = $this->repository->find(AcquiringPaymentStatus::REGISTERED, $columns);

        $this->assertInstanceOf(AcquiringPaymentStatus::class, $model);
        $this->assertEquals(AcquiringPaymentStatus::REGISTERED, $model->id);
        $this->assertCount(2, $model->getAttributes());
        $this->assertEquals($columns, array_keys($model->getAttributes()));
    }

    /**
     * @test
     */
    public function find_or_fail_method_returns_model()
    {
        $columns = ['id', 'name', 'created_at'];
        $model = $this->repository->findOrFail(AcquiringPaymentStatus::REVERSED, $columns);

        $this->assertInstanceOf(AcquiringPaymentStatus::class, $model);
        $this->assertEquals(AcquiringPaymentStatus::REVERSED, $model->id);
        $this->assertCount(3, $model->getAttributes());
        $this->assertEquals($columns, array_keys($model->getAttributes()));
    }

    /**
     * @test
     */
    public function find_or_fail_method_throws_exception_when_cannot_find_model()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->repository->findOrFail(9010);
    }

    /**
     * @test
     */
    public function find_by_bank_id_method_returns_model()
    {
        // id статуса удержания предавторизационной суммы
        $held = 1;
        $columns = ['id', 'name', 'full_name', 'created_at'];
        $model = $this->repository->findByBankId($held, $columns);

        $this->assertInstanceOf(AcquiringPaymentStatus::class, $model);
        $this->assertEquals(AcquiringPaymentStatus::HELD, $model->id);
        $this->assertCount(4, $model->getAttributes());
        $this->assertEquals($columns, array_keys($model->getAttributes()));
    }
}
