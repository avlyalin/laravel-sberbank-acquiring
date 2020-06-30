<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Factories;

use Avlyalin\SberbankAcquiring\Models\SberbankPayment;
use Avlyalin\SberbankAcquiring\Models\ApplePayPayment;
use Avlyalin\SberbankAcquiring\Models\GooglePayPayment;
use Avlyalin\SberbankAcquiring\Models\SamsungPayPayment;

class PaymentsFactory
{
    /**
     * @param array $attributes
     *
     * @return SberbankPayment
     */
    public function createSberbankPayment(array $attributes = []): SberbankPayment
    {
        $sberbankPayment = new SberbankPayment($attributes);
        $sberbankPayment->save();
        return $sberbankPayment;
    }

    /**
     * @param array $attributes
     *
     * @return ApplePayPayment
     */
    public function createApplePayPayment(array $attributes = []): ApplePayPayment
    {
        $applePayPayment = new ApplePayPayment($attributes);
        $applePayPayment->save();
        return $applePayPayment;
    }

    /**
     * @param array $attributes
     *
     * @return SamsungPayPayment
     */
    public function createSamsungPayPayment(array $attributes = []): SamsungPayPayment
    {
        $samsungPayPayment = new SamsungPayPayment($attributes);
        $samsungPayPayment->save();
        return $samsungPayPayment;
    }

    /**
     * @param array $attributes
     *
     * @return GooglePayPayment
     */
    public function createGooglePayPayment(array $attributes = []): GooglePayPayment
    {
        $googlePayPayment = new GooglePayPayment($attributes);
        $googlePayPayment->save();
        return $googlePayPayment;
    }
}
