<?php

namespace app\Provider;

use kosuha606\VirtualAdmin\Domains\Transaction\TransactionVm;
use kosuha606\VirtualModel\VirtualModelProvider;

class TransactionProvider extends VirtualModelProvider
{
    /**
     * @return string
     */
    public function type()
    {
        return TransactionVm::KEY;
    }

    /**
     * @return string
     */
    public function environemnt(): string
    {
        return TransactionVm::KEY;
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return null
     */
    protected function findOne($modelClass, $config)
    {
        return null;
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return null
     */
    protected function findMany($modelClass, $config)
    {
        return null;
    }

    /**
     * @param string $name
     */
    public static function begin($name = 'default'): void
    {
        // nothing
    }

    /**
     * @param string $name
     */
    public static function commit($name = 'default'): void
    {
        // nothing
    }

    /**
     * @param string $name
     */
    public static function rollback($name = 'default'): void
    {
        // nothing mongo
    }
}
