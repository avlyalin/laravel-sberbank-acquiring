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
        if (empty($value)) {
            throw new Exception('Error: config/sberbank-acquiring.php not loaded and defaults could not be merged');
        }
        return $value;
    }
}
