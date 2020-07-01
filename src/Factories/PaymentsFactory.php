<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Factories;

use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentOperation;
use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;

class PaymentsFactory
{
    /**
     * @param array $attributes
     *
     * @return AcquiringPayment
     */
    public function createAcquiringPayment(array $attributes = []): AcquiringPayment
    {
        return new AcquiringPayment($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return SberbankPayment
     */
    public function createSberbankPayment(array $attributes = []): SberbankPayment
    {
        return new SberbankPayment($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return ApplePayPayment
     */
    public function createApplePayPayment(array $attributes = []): ApplePayPayment
    {
        return new ApplePayPayment($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return SamsungPayPayment
     */
    public function createSamsungPayPayment(array $attributes = []): SamsungPayPayment
    {
        return new SamsungPayPayment($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return GooglePayPayment
     */
    public function createGooglePayPayment(array $attributes = []): GooglePayPayment
    {
        return new GooglePayPayment($attributes);
    }

    /**
     * @param array $attributes
     *
     * @return AcquiringPaymentOperation
     */
    public function createPaymentOperation(array $attributes = []): AcquiringPaymentOperation
    {
        return new AcquiringPaymentOperation($attributes);
    }
}
