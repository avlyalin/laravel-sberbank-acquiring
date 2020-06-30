<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface PaymentInterface
{
    /**
     * Базовая модель платежа
     *
     * @return BelongsTo
     */
    public function basePayment(): BelongsTo;
}
