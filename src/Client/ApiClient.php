<?php

namespace Avlyalin\SberbankAcquiring\Client;

use Avlyalin\SberbankAcquiring\Client\Curl\Curl;

class ApiClient implements ApiClientInterface
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
    public function __construct(array $options = [])
    {
        if (isset($options['httpClient'])) {
            $this->httpClient = $options['httpClient'];
        }

        if (isset($options['userName']) && isset($options['password'])) {
            $this->userName = $options['userName'];
            $this->password = $options['password'];
        } elseif (isset($options['token'])) {
            $this->token = $options['token'];
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
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\ErrorResponseException
     */
    public function requestWithAuth(
        string $pathName,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        if (empty($params['userName']) === false && empty($params['password']) === false) {
            unset($params['token']);
        } elseif (empty($this->userName) === false && empty($this->password) === false) {
            unset($params['token']);
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        } elseif (empty($params['token']) === false) {
            unset($params['userName']);
            unset($params['password']);
        } elseif (empty($this->token) === false) {
            unset($params['userName']);
            unset($params['password']);
            $params['token'] = $this->token;
        } else {
            throw new \InvalidArgumentException('You must specify "userName"/"password" pair or "token"');
        }
        return $this->request($pathName, $params, $method, $headers);
    }

    /**
     * @param string $pathName
     * @param array $params
     * @param string $method
     * @param array $headers
     *
     * @return array
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\JsonException
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\ErrorResponseException
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
        if (isset($this->httpClient)) {
            if ($this->httpClient instanceof HttpClientInterface) {
                return $this->httpClient;
            }
            throw new \InvalidArgumentException('"httpClient" must be instance of HttpClientInterface');
        }
        return new HttpClient(new Curl());
    }
}
