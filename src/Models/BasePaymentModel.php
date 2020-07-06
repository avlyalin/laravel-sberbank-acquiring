<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Models;

use Illuminate\Database\Eloquent\Relations\MorphOne;

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
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function basePayment(): MorphOne
    {
        return $this->morphOne(AcquiringPayment::class, 'payment');
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
                throw new \InvalidArgumentException("Param $param not found in \$acquiringParamsMap");
            }
            $attribute = $this->acquiringParamsMap[$param];
            $attributes[$attribute] = $value;
        }
        return $this->fill($attributes);
    }
}
