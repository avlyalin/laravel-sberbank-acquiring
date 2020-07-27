<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Tests\Commands;

use Avlyalin\SberbankAcquiring\Client\Client;
use Avlyalin\SberbankAcquiring\Commands\UpdateStatusCommand;
use Avlyalin\SberbankAcquiring\Events\UpdateStatusCommandHasFailed;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentRepository;
use Avlyalin\SberbankAcquiring\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

class UpdateStatusCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /**
     * @test
     */
    public function it_can_use_custom_statuses()
    {
        $statuses = [
            AcquiringPaymentStatus::AUTH_DECLINED,
            AcquiringPaymentStatus::REVERSED,
            AcquiringPaymentStatus::REFUNDED,
        ];
        $this->mockAcquiringPaymentRepository('getByStatus', $statuses, new Collection());
        $this->mockClient();

        $this->artisan('sberbank-acquiring:update-statuses', ['--id' => [8, 5, 6]])->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_use_default_statuses()
    {
        $this->mockAcquiringPaymentRepository('getByStatus', UpdateStatusCommand::STATUSES, new Collection());
        $this->mockClient();

        $this->artisan('sberbank-acquiring:update-statuses')->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_updates_payments_statuses()
    {
        $payment1 = $this->createAcquiringPayment(['status_id' => AcquiringPaymentStatus::NEW]);
        $payment2 = $this->createAcquiringPayment(['status_id' => AcquiringPaymentStatus::REVERSED]);
        $payment3 = $this->createAcquiringPayment(['status_id' => AcquiringPaymentStatus::REGISTERED]);

        $client = \Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('getOrderStatusExtended')->once()->with($payment1->id);
        $client->shouldReceive('getOrderStatusExtended')->once()->with($payment2->id);
        $client->shouldReceive('getOrderStatusExtended')->once()->with($payment3->id);
        $this->app->instance(Client::class, $client);

        $this->artisan('sberbank-acquiring:update-statuses', ['--id' => [1, 5, 2]])->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_should_fail_and_emit_event_when_get_order_status_throws_exception()
    {
        $payment1 = $this->createAcquiringPayment(['status_id' => AcquiringPaymentStatus::NEW]);
        $payment2 = $this->createAcquiringPayment(['status_id' => AcquiringPaymentStatus::REFUNDED]);
        $payment3 = $this->createAcquiringPayment(['status_id' => AcquiringPaymentStatus::ACS_AUTH]);

        $client = \Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('getOrderStatusExtended')->once()->with($payment1->id);
        $client->shouldReceive('getOrderStatusExtended')->once()->with($payment2->id)
            ->andThrow(\Exception::class, "Cannot find payment with orderId $payment2->bank_order_id");
        $client->shouldReceive('getOrderStatusExtended')->once()->with($payment3->id);
        $this->app->instance(Client::class, $client);

        $this->artisan('sberbank-acquiring:update-statuses', ['--id' => [1, 6, 7]])->assertExitCode(1);

        Event::assertDispatched(UpdateStatusCommandHasFailed::class);
    }

    private function mockAcquiringPaymentRepository(string $method, array $args, $returnValue)
    {
        $repository = \Mockery::mock(AcquiringPaymentRepository::class)->makePartial();
        $repository->shouldReceive($method)->with($args)->andReturn($returnValue);
        $this->app->instance(AcquiringPaymentRepository::class, $repository);
    }

    private function mockClient()
    {
        $client = \Mockery::mock(Client::class)->makePartial();
        $this->app->instance(Client::class, $client);
    }
}
