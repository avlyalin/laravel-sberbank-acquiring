<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Repositories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcquiringPaymentStatusRepository extends BaseRepository
{

    /**
     * AcquiringPaymentStatus constructor.
     *
     * @param AcquiringPaymentStatus $acquiringPaymentStatus
     */
    public function __construct(AcquiringPaymentStatus $acquiringPaymentStatus)
    {
        parent::__construct($acquiringPaymentStatus);
    }

    /**
     * Поиск модели через id в системе банка
     *
     * @param int $bankId id статуса в системе банка
     * @param array|string[] $columns
     *
     * @return Builder|Model|object|null
     */
    public function findByBankId(int $bankId, array $columns = ['*'])
    {
        return $this->model->newQuery()->where('bank_id', $bankId)->first($columns);
    }
}
