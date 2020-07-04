<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Repositories;

use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DictAcquiringPaymentStatusRepository extends BaseRepository
{

    /**
     * DictAcquiringPaymentStatus constructor.
     *
     * @param DictAcquiringPaymentStatus $acquiringPaymentStatus
     */
    public function __construct(DictAcquiringPaymentStatus $acquiringPaymentStatus)
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
