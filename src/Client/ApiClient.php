<?php

namespace Avlyalin\SberbankAcquiring\Client;

use Avlyalin\SberbankAcquiring\Client\Curl\Curl;

class ApiClient implements ClientInterface
{

    /**
     * @var mixed|null
     */
    private $userName;

    /**
     * @var mixed|null
     */
    private $password;

    /**
     * @var mixed|null
     */
    private $token;

    /**
     * @var mixed|null
     */
    private $httpClient;

    /**
     * @var mixed|string
     */
    private $baseUri;

    /**
     * Client constructor.
     *
     * <code>
     * $options = array(
     *   'userName'   => 'username', // Логин служебной учётной записи продавца
     *   'password'   => 'password', // Пароль служебной учётной записи продавца
     *   'token'   => 'token', // Значение, используемое для аутентификации продавца вместо пары userName/password
     *   'httpClient'   => 'httpClient', // HTTP-клиент
     *   'baseUri' => 'baseUri', // Адрес сервера
     * );
     *
     * </code>
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (isset($options['userName']) && isset($options['password'])) {
            $this->userName = $options['userName'];
            $this->password = $options['password'];
        } elseif (isset($options['token'])) {
            $this->token = $options['token'];
        } else {
            throw new \InvalidArgumentException('"token" or "userName"/"password" pair must be passed');
        }

        if (isset($options['httpClient'])) {
            if ($options['httpClient'] instanceof HttpClientInterface) {
                $this->httpClient = $options['httpClient'];
            } else {
                throw new \InvalidArgumentException('"httpClient" must be instance of HttpClientInterface');
            }
        }

        $this->baseUri = $options['baseUri'] ?? self::URI_PROD;
    }

    /**
     * @inheritDoc
     */
    public function register(
        int $amount,
        string $returnUrl,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        $params['amount'] = $amount;
        $params['returnUrl'] = $returnUrl;
        return $this->requestWithAuth(self::PATH_REGISTER, $params, $method, $headers);
    }

    /**
     * @inheritDoc
     */
    public function registerPreAuth(
        int $amount,
        string $returnUrl,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        $params['amount'] = $amount;
        $params['returnUrl'] = $returnUrl;
        return $this->requestWithAuth(self::PATH_REGISTER_PRE_AUTH, $params, $method, $headers);
    }

    /**
     * @inheritDoc
     */
    public function deposit(
        $orderId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        return $this->requestWithAuth(self::PATH_DEPOSIT, $params, $method, $headers);
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
        $params['orderId'] = $orderId;
        return $this->requestWithAuth(self::PATH_REVERSE, $params, $method, $headers);
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
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        return $this->requestWithAuth(self::PATH_REFUND, $params, $method, $headers);
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusExtended(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        if (isset($params['orderId']) === false && isset($params['orderNumber']) === false) {
            throw new \InvalidArgumentException('"orderId" either "orderNumber" is required');
        }
        return $this->requestWithAuth(self::PATH_GET_ORDER_STATUS_EXTENDED, $params, $method, $headers);
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
        $params['merchant'] = $merchant;
        $params['paymentToken'] = $paymentToken;
        return $this->request(self::PATH_APPLE_PAY, $params, $method, $headers);
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
        $params['merchant'] = $merchant;
        $params['paymentToken'] = $paymentToken;
        return $this->request(self::PATH_SAMSUNG_PAY, $params, $method, $headers);
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
        $params['merchant'] = $merchant;
        $params['paymentToken'] = $paymentToken;
        $params['amount'] = $amount;
        $params['returnUrl'] = $returnUrl;
        return $this->request(self::PATH_GOOGLE_PAY, $params, $method, $headers);
    }

    /**
     * @inheritDoc
     */
    public function getReceiptStatus(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        if (
            isset($params['orderId']) === false &&
            isset($params['orderNumber']) === false &&
            isset($params['uuid']) === false
        ) {
            throw new \InvalidArgumentException('You must specify "orderId" or "orderNumber" or "uuid" param');
        }
        return $this->requestWithAuth(self::PATH_GET_RECEIPT_STATUS, $params, $method, $headers);
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
        $params['bindingId'] = $bindingId;
        return $this->requestWithAuth(self::PATH_BIND, $params, $method, $headers);
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
        $params['bindingId'] = $bindingId;
        return $this->requestWithAuth(self::PATH_UNBIND, $params, $method, $headers);
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
        $params['clientId'] = $clientId;
        return $this->request(self::PATH_GET_BINDINGS, $params, $method, $headers);
    }

    /**
     * @inheritDoc
     */
    public function getBindingsByCardOrId(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        return $this->requestWithAuth(self::PATH_GET_BINDINGS_BY_CARD_OR_ID, $params, $method, $headers);
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
        $params['bindingId'] = $bindingId;
        $params['newExpiry'] = $newExpiry;
        return $this->requestWithAuth(self::PATH_EXTEND_BINDING, $params, $method, $headers);
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
        $params['pan'] = $pan;
        return $this->requestWithAuth(self::PATH_VERIFY_ENROLLMENT, $params, $method, $headers);
    }

    /**
     * @param string $pathName
     * @param array $params
     * @param string $method
     * @param array $headers
     *
     * @return array
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\JsonException
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\OperationException
     */
    public function requestWithAuth(
        string $pathName,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        $authParams = $this->getAuthParams();
        $requestParams = array_merge($authParams, $params);
        return $this->request($pathName, $requestParams, $method, $headers);
    }

    /**
     * @param string $pathName
     * @param array $params
     * @param string $method
     * @param array $headers
     *
     * @return array
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\JsonException
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\OperationException
     */
    public function request(
        string $pathName,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        $uri = $this->buildUri($pathName);
        $httpClient = $this->getHttpClient();
        $response = $httpClient->request($uri, $method, $params, $headers);
        $sberbankResponse = new SberbankResponse($response);
        return $sberbankResponse->getFormattedResponse();
    }

    /**
     * Формирует абсолютный URI
     *
     * @param string $pathName Относительный путь
     *
     * @return string
     */
    private function buildUri(string $pathName): string
    {
        $baseUri = rtrim($this->baseUri, '/');
        $relativePath = ltrim($pathName, '/');
        return "$baseUri/$relativePath";
    }

    /**
     * @return HttpClientInterface
     */
    private function getHttpClient(): HttpClientInterface
    {
        if ($this->httpClient) {
            return $this->httpClient;
        }
        return new HttpClient(new Curl());
    }

    private function getAuthParams(): array
    {
        if ($this->userName && $this->password) {
            return [
                'userName' => $this->userName,
                'password' => $this->password,
            ];
        }
        return ['token' => $this->token];
    }
}
