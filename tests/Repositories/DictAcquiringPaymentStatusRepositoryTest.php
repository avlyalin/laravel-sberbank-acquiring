<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Repositories;

use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Repositories\DictAcquiringPaymentStatusRepository;
use Avlyalin\SberbankAcquiring\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DictAcquiringPaymentStatusRepositoryTest extends TestCase
{
    /**
     * @var DictAcquiringPaymentStatusRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(DictAcquiringPaymentStatusRepository::class);
    }

    /**
     * @test
     */
    public function find_method_returns_model()
    {
        $columns = ['id', 'full_name'];
        $model = $this->repository->find(DictAcquiringPaymentStatus::REGISTERED, $columns);

        $this->assertInstanceOf(DictAcquiringPaymentStatus::class, $model);
        $this->assertEquals(DictAcquiringPaymentStatus::REGISTERED, $model->id);
        $this->assertCount(2, $model->getAttributes());
        $this->assertEquals($columns, array_keys($model->getAttributes()));
    }

    /**
     * @test
     */
    public function find_or_fail_method_returns_model()
    {
        $columns = ['id', 'name', 'created_at'];
        $model = $this->repository->findOrFail(DictAcquiringPaymentStatus::REVERSED, $columns);

        $this->assertInstanceOf(DictAcquiringPaymentStatus::class, $model);
        $this->assertEquals(DictAcquiringPaymentStatus::REVERSED, $model->id);
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

        $this->assertInstanceOf(DictAcquiringPaymentStatus::class, $model);
        $this->assertEquals(DictAcquiringPaymentStatus::HELD, $model->id);
        $this->assertCount(4, $model->getAttributes());
        $this->assertEquals($columns, array_keys($model->getAttributes()));
    }
}
