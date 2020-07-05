<?php

namespace Avlyalin\SberbankAcquiring\Traits;

use Avlyalin\SberbankAcquiring\Exceptions\AcquiringException;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

trait HasConfig
{
    /**
     * @param string $tableNameKey
     *
     * @return string
     * @throws Exception
     */
    public function getTableName(string $tableNameKey): string
    {
        return $this->getConfigParam("table_names.$tableNameKey");
    }

    /**
     * Возвращает массив параметров для авторизации
     *
     * @return array
     * @throws Exception
     */
    public function getConfigAuthParams(): array
    {
        return $this->getConfigParam('auth');
    }

    /**
     * Возвращает логин продавца
     *
     * @return string
     * @throws Exception
     */
    public function getConfigMerchantLoginParam(): string
    {
        return $this->getConfigParam('merchant_login');
    }

    /**
     * @param string $key
     *
     * @return Repository|Application|mixed
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\AcquiringException
     */
    public function getConfigParam(string $key)
    {
        $value = config("sberbank-acquiring.$key");
        if (is_null($value)) {
            throw new AcquiringException(
                "Error: cannot find key \"$key\" in config/sberbank-acquiring.php. Config may not be loaded."
            );
        }
        return $value;
    }
}
