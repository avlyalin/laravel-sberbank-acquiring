<?php

namespace Avlyalin\SberbankAcquiring\Client;

class Client implements ClientInterface
{

    /**
     * @inheritDoc
     */
    public function register(int $amount, string $returnUrl, array $params = []): array
    {
        // TODO: Implement register() method.
    }

    /**
     * @inheritDoc
     */
    public function registerPreAuth(int $amount, string $returnUrl, array $params = []): array
    {
        // TODO: Implement registerPreAuth() method.
    }

    /**
     * @inheritDoc
     */
    public function deposit($orderId, int $amount, array $params = []): array
    {
        // TODO: Implement deposit() method.
    }

    /**
     * @inheritDoc
     */
    public function reverse($orderId, array $params = []): array
    {
        // TODO: Implement reverse() method.
    }

    /**
     * @inheritDoc
     */
    public function refund($orderId, int $amount, array $params = []): array
    {
        // TODO: Implement refund() method.
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatus(array $params = []): array
    {
        // TODO: Implement getOrderStatus() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithApplePay(string $merchant, string $paymentToken, array $params = []): array
    {
        // TODO: Implement payWithApplePay() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithSamsungPay(string $merchant, string $paymentToken, array $params = []): array
    {
        // TODO: Implement payWithSamsungPay() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithGooglePay(
        string $merchant,
        string $paymentToken,
        int $amount,
        string $returnUrl,
        array $params = []
    ): array {
        // TODO: Implement payWithGooglePay() method.
    }

    /**
     * @inheritDoc
     */
    public function getReceiptStatus(array $params = []): array
    {
        // TODO: Implement getReceiptStatus() method.
    }

    /**
     * @inheritDoc
     */
    public function bindCard(string $bindingId, array $params = []): array
    {
        // TODO: Implement bindCard() method.
    }

    /**
     * @inheritDoc
     */
    public function unBindCard(string $bindingId, array $params = []): array
    {
        // TODO: Implement unBindCard() method.
    }

    /**
     * @inheritDoc
     */
    public function getBindings(string $clientId, array $params = []): array
    {
        // TODO: Implement getBindings() method.
    }

    /**
     * @inheritDoc
     */
    public function getBindingsByCardOrId(array $params = []): array
    {
        // TODO: Implement getBindingsByCardOrId() method.
    }

    /**
     * @inheritDoc
     */
    public function extendBinding(string $bindingId, int $newExpiry, array $params = []): array
    {
        // TODO: Implement extendBinding() method.
    }

    /**
     * @inheritDoc
     */
    public function verifyEnrollment(string $pan, array $params = []): array
    {
        // TODO: Implement verifyEnrollment() method.
    }
}
