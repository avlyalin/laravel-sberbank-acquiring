<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AcquiringPayment extends BaseModel
{
    protected $tableNameKey = 'payments';

    protected $fillable = [
        'bank_order_id',
        'status_id',
        'system_id',
        'payment_type',
        'payment_id',
    ];

    /**
     * Операции по платежу
     *
     * @return HasMany
     */
    public function operations(): HasMany
    {
        return $this->hasMany(AcquiringPaymentOperation::class, 'payment_id', 'id');
    }

    /**
     * Платежная система
     *
     * @return BelongsTo
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(DictAcquiringPaymentSystem::class, 'system_id', 'id');
    }

    /**
     * Статус платежа
     *
     * @return BelongsTo
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DictAcquiringPaymentStatus::class, 'status_id', 'id');
    }

    /**
     * Модель платежа
     *
     * @return MorphTo
     */
    public function payment(): MorphTo
    {
        return $this->morphTo();
    }
}
