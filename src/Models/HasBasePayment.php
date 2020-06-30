<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasBasePayment
{
    /**
     * Базовая модель платежа
     *
     * @return BelongsTo
     */
    public function basePayment(): BelongsTo
    {
        return $this->belongsTo(AcquiringPayment::class, 'payment_id', 'id');
    }
}
