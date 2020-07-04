<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Client;

use Avlyalin\SberbankAcquiring\Exceptions\JsonException;
use Avlyalin\SberbankAcquiring\Exceptions\ResponseProcessingException;
use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Avlyalin\SberbankAcquiring\Exceptions\ErrorResponseException;
use Avlyalin\SberbankAcquiring\Factories\PaymentsFactory;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentOperationType;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class Client
{
    use HasConfig;

    /**
     * @var ApiClientInterface
     */
    private $apiClient;
    /**
     * @var PaymentsFactory
     */
    private $paymentsFactory;
    /**
     * @var AcquiringPaymentRepository
     */
    private $acquiringPaymentRepository;

    /**
     * Client constructor.
     *
     * @param ApiClientInterface $apiClient
     * @param PaymentsFactory $paymentsFactory
     * @param AcquiringPaymentRepository $acquiringPaymentRepository
     */
    public function __construct(
        ApiClientInterface $apiClient,
        PaymentsFactory $paymentsFactory,
        AcquiringPaymentRepository $acquiringPaymentRepository
    ) {
        $this->apiClient = $apiClient;
        $this->paymentsFactory = $paymentsFactory;
        $this->acquiringPaymentRepository = $acquiringPaymentRepository;
    }

    /**
     * @param int $amount    Сумма платежа в минимальных единицах валюты
     * @param array $params  Дополнительные параметры
     * @param string $method Тип HTTP-запроса
     * @param array $headers Хэдеры HTTP-клиента
     *
     * @return AcquiringPayment
     *
     * @throws JsonException
     * @throws ResponseProcessingException
     * @throws \InvalidArgumentException
     * @throws Throwable
     */
    public function register(
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        return $this->performRegister(
            DictAcquiringPaymentOperationType::REGISTER,
            $amount,
            $params,
            $method,
            $headers
        );
    }

    /**
     * @param int $amount    Сумма платежа в минимальных единицах валюты
     * @param array $params  Дополнительные параметры
     * @param string $method Тип HTTP-запроса
     * @param array $headers Хэдеры HTTP-клиента
     *
     * @return AcquiringPayment
     *
     * @throws JsonException
     * @throws ResponseProcessingException
     * @throws \InvalidArgumentException
     * @throws Throwable
     */
    public function registerPreAuth(
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        return $this->performRegister(
            DictAcquiringPaymentOperationType::REGISTER_PRE_AUTH,
            $amount,
            $params,
            $method,
            $headers
        );
    }

    public function deposit(
        int $acquiringPaymentId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        // TODO
    }

    /**
     * @inheritDoc
     */
    public function reverse(
        $orderId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement reverse() method.
    }

    /**
     * @inheritDoc
     */
    public function refund(
        $orderId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement refund() method.
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusExtended(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getOrderStatusExtended() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithApplePay(
        string $merchant,
        string $paymentToken,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement payWithApplePay() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithSamsungPay(
        string $merchant,
        string $paymentToken,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
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
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement payWithGooglePay() method.
    }

    /**
     * @inheritDoc
     */
    public function getReceiptStatus(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getReceiptStatus() method.
    }

    /**
     * @inheritDoc
     */
    public function bindCard(
        string $bindingId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement bindCard() method.
    }

    /**
     * @inheritDoc
     */
    public function unBindCard(
        string $bindingId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement unBindCard() method.
    }

    /**
     * @inheritDoc
     */
    public function getBindings(
        string $clientId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getBindings() method.
    }

    /**
     * @inheritDoc
     */
    public function getBindingsByCardOrId(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getBindingsByCardOrId() method.
    }

    /**
     * @inheritDoc
     */
    public function extendBinding(
        string $bindingId,
        int $newExpiry,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement extendBinding() method.
    }

    /**
     * @inheritDoc
     */
    public function verifyEnrollment(
        string $pan,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement verifyEnrollment() method.
    }

    /**
     * @param int $operationId
     * @param int $amount
     * @param array $params
     * @param string $method
     * @param array $headers
     *
     * @return AcquiringPayment
     * @throws JsonException
     * @throws ResponseProcessingException
     * @throws \InvalidArgumentException
     * @throws Throwable
     */
    private function performRegister(
        int $operationId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        $returnUrl = $params['returnUrl'] ?? $this->getConfigParam('params.return_url');
        unset($params['returnUrl']);

        $params['failUrl'] = $params['failUrl'] ?? $this->getConfigParam('params.fail_url');

        $requestData = array_merge(['amount' => $amount, 'returnUrl' => $returnUrl], $params);

        $payment = $this->paymentsFactory->createSberbankPayment();
        $payment->fillWithSberbankParams($requestData);
        $payment->saveOrFail();

        $acquiringPayment = $this->paymentsFactory->createAcquiringPayment();
        $acquiringPayment->fill([
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
            'status_id' => DictAcquiringPaymentStatus::NEW,
        ]);
        $acquiringPayment->payment()->associate($payment);
        $acquiringPayment->saveOrFail();

        $operation = $this->paymentsFactory->createPaymentOperation();
        $operation->fill([
            'user_id' => Auth::id(),
            'type_id' => $operationId,
            'request_json' => $requestData,
        ]);
        $operation->payment()->associate($acquiringPayment);
        $operation->saveOrFail();

        $response = $this->apiClient->register(
            $amount,
            $returnUrl,
            $this->addAuthParams($params),
            $method,
            $headers
        );

        /** @var SberbankResponse $responseData */
        $responseData = $response->getResponseArray();

        $errorMessage = '';
        $paymentSaved = $payment->update(['bank_form_url' => $responseData['formUrl']]);
        if (!$paymentSaved) {
            $errorMessage .= 'Error updating SberbankPayment. ';
        }

        $acquiringPaymentSaved = $acquiringPayment->update([
            'bank_order_id' => $responseData['orderId'],
            'status_id' => $response->isOk() ? DictAcquiringPaymentStatus::REGISTERED
                : DictAcquiringPaymentStatus::ERROR,
        ]);
        if (!$acquiringPaymentSaved) {
            $errorMessage .= 'Error updating AcquiringPayment. ';
        }

        $operationSaved = $operation->update(['response_json' => $responseData]);
        if (!$operationSaved) {
            $errorMessage .= 'Error updating AcquiringPaymentOperation. ';
        }

        if (!empty($errorMessage)) {
            $response = (string)$response->getResponse();
            throw new ResponseProcessingException($errorMessage . "Response: $response");
        }

        return $acquiringPayment;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws Exception
     */
    private function addAuthParams(array $params = []): array
    {
        $authParams = [];
        $auth = $this->getConfigParam('auth');
        if (empty($auth['userName']) === false && empty($auth['password']) === false) {
            $authParams = [
                'userName' => $auth['userName'],
                'password' => $auth['password'],
            ];
        } elseif (empty($auth['token']) === false) {
            $authParams = ['token' => $auth['token']];
        }
        return array_merge($authParams, $params);
    }
}
