<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Repositories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Illuminate\Database\Eloquent\Collection;

class AcquiringPaymentRepository extends BaseRepository
{

    /**
     * AcquiringPaymentRepository constructor.
     *
     * @param AcquiringPayment $acquiringPayment
     */
    public function __construct(AcquiringPayment $acquiringPayment)
    {
        parent::__construct($acquiringPayment);
    }

    /**
     * Возвращает коллекцию платежей с указанными статусами
     *
     * @param array $statuses
     * @param string[] $columns
     *
     * @return Collection
     */
    public function getByStatus(array $statuses, $columns = ['*']): Collection
    {
        return $this->model->newQuery()->whereIn('status_id', $statuses)->get($columns);
    }
}
