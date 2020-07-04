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
     * @return AcquiringPayment
     */
    public function createAcquiringPayment(): AcquiringPayment
    {
        return new AcquiringPayment();
    }

    /**
     * @return SberbankPayment
     */
    public function createSberbankPayment(): SberbankPayment
    {
        return new SberbankPayment();
    }

    /**
     * @return ApplePayPayment
     */
    public function createApplePayPayment(): ApplePayPayment
    {
        return new ApplePayPayment();
    }

    /**
     * @return SamsungPayPayment
     */
    public function createSamsungPayPayment(): SamsungPayPayment
    {
        return new SamsungPayPayment();
    }

    /**
     * @return GooglePayPayment
     */
    public function createGooglePayPayment(): GooglePayPayment
    {
        return new GooglePayPayment();
    }

    /**
     * @return AcquiringPaymentOperation
     */
    public function createPaymentOperation(): AcquiringPaymentOperation
    {
        return new AcquiringPaymentOperation();
    }
}
