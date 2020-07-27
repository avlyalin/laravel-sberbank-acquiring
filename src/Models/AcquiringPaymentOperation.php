<?php

namespace Avlyalin\SberbankAcquiring\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquiringPaymentOperation extends BaseModel
{
    protected $tableNameKey = 'payment_operations';

    protected $casts = [
        'request_json' => 'array',
        'response_json' => 'array',
    ];

    protected $fillable = [
        'payment_id',
        'user_id',
        'type_id',
        'request_json',
        'response_json',
    ];

    /**
     * Пользователь-инициатор операции
     *
     * @return BelongsTo|null
     * @throws Exception
     */
    public function user(): ?BelongsTo
    {
        return $this->belongsTo(
            $this->getConfigParam('user.model'),
            'user_id',
            $this->getConfigParam('user.primary_key')
        );
    }

    /**
     * Платеж
     *
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(AcquiringPayment::class, 'payment_id', 'id');
    }

    /**
     * Тип операции
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AcquiringPaymentOperationType::class, 'type_id', 'id');
    }
}
