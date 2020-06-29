<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Client\Curl;

class Curl implements CurlInterface
{
    /**
     * @var false|resource
     */
    private $curl;

    /**
     * @inheritDoc
     */
    public function initialize(string $url = '')
    {
        $this->curl = empty($url) ? curl_init() : curl_init($url);
        return $this->curl;
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options = [])
    {
        return curl_setopt_array($this->curl, $options);
    }

    /**
     * @inheritDoc
     */
    public function setOption(int $option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * @inheritDoc
     */
    public function setHeader(array $header)
    {
        foreach ($header as $key => $value) {
            $this->setOption(CURLOPT_HTTPHEADER, ["$key: $value"]);
        }
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        return curl_exec($this->curl);
    }

    /**
     * @inheritDoc
     */
    public function getInfo(int $option)
    {
        return curl_getinfo($this->curl, $option);
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return curl_error($this->curl);
    }

    /**
     * @inheritDoc
     */
    public function getErrno()
    {
        return curl_errno($this->curl);
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return curl_close($this->curl);
    }
}
