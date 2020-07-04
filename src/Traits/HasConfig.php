<?php

namespace Avlyalin\SberbankAcquiring\Traits;

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
     * @param string $key
     *
     * @return Repository|Application|mixed
     * @throws Exception
     */
    public function getConfigParam(string $key)
    {
        $value = config("sberbank-acquiring.$key");
        if (is_null($value)) {
            throw new Exception(
                "Error: cannot find key \"$key\" in config/sberbank-acquiring.php. Config may not be loaded."
            );
        }
        return $value;
    }
}
