<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class BasePaymentModel extends BaseModel
{
    /**
     * Массив: параметр Сбербанка => поле в БД
     *
     * @var array
     */
    protected $acquiringParamsMap = [];

    /**
     * Базовая модель платежа
     *
     * @return BelongsTo
     */
    public function basePayment(): BelongsTo
    {
        return $this->belongsTo(AcquiringPayment::class, 'payment_id', 'id');
    }

    /**
     * Заполнение атрибутов, используя массив параметров, отправляемых в Сбербанк
     *
     * @param array $sberbankParams
     *
     * @return self
     */
    public function fillWithSberbankParams(array $sberbankParams): self
    {
        $attributes = [];
        foreach ($sberbankParams as $param => $value) {
            if (isset($this->acquiringParamsMap[$param]) === false) {
                throw new \InvalidArgumentException("Param $param not found in \$sberbankParamsMap");
            }
            $attribute = $this->acquiringParamsMap[$param];
            $attributes[$attribute] = $value;
        }
        return $this->fill($attributes);
    }
}
