<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Repositories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;

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
}
