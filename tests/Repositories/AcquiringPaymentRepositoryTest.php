<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Repositories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
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

    /**
     * @test
     */
    public function get_by_status_method_returns_payments_collection()
    {
        $newPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::NEW]);
        $registeredPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::REGISTERED]);
        $registeredPayment2 = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::REGISTERED]);
        $errorPayment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::ERROR]);

        $newPayments = $this->repository->getByStatus([DictAcquiringPaymentStatus::NEW]);
        $registeredPayments = $this->repository->getByStatus([DictAcquiringPaymentStatus::REGISTERED]);
        $errorPayments = $this->repository->getByStatus([DictAcquiringPaymentStatus::ERROR]);
        $acsAuthPayments = $this->repository->getByStatus([DictAcquiringPaymentStatus::ACS_AUTH]);

        $this->assertCount(1, $newPayments);
        $this->assertTrue($newPayments->contains($newPayment));

        $this->assertCount(2, $registeredPayments);
        $this->assertTrue($registeredPayments->contains($registeredPayment));
        $this->assertTrue($registeredPayments->contains($registeredPayment2));

        $this->assertCount(1, $errorPayments);
        $this->assertTrue($errorPayments->contains($errorPayment));

        $this->assertCount(0, $acsAuthPayments);
    }

    /**
     * @test
     */
    public function get_by_status_method_returns_payments_collection_with_specified_columns()
    {
        $payment = $this->createAcquiringPayment(['status_id' => DictAcquiringPaymentStatus::NEW]);
        $payments = $this->repository->getByStatus([DictAcquiringPaymentStatus::NEW], ['id', 'bank_order_id']);

        $this->assertTrue($payments->contains($payment));

        $attributes = array_keys($payments->first()->getAttributes());
        $this->assertEquals(['id', 'bank_order_id'], $attributes);
    }
}
