<?php

namespace app\Provider;

use kosuha606\VirtualModel\VirtualModelProvider;

class TransactionProvider extends VirtualModelProvider
{
    public const TRANSACTION = 'transaction';

    /**
     * @return string
     */
    public function type()
    {
        return self::TRANSACTION;
    }

    /**
     * @return string
     */
    public function environemnt(): string
    {
        return self::TRANSACTION;
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return null
     */
    protected function findOne(string $modelClass, array $config)
    {
        return null;
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return null
     */
    protected function findMany(string $modelClass, array $config)
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
