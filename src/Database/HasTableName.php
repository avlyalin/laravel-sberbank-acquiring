<?php

namespace Avlyalin\SberbankAcquiring\Database;

trait HasTableName
{
    /**
     * @param string $tableNameKey
     * @return string
     * @throws \Exception
     */
    public function getTableName(string $tableNameKey): string
    {
        $tableName = config("sberbank-acquiring.table_names.${tableNameKey}");
        if (empty($tableName)) {
            throw new \Exception('Error: config/sberbank-acquiring.php not loaded and defaults could not be merged');
        }
        return $tableName;
    }
}
