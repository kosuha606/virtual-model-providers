<?php

namespace app\Provider;

use kosuha606\VirtualModel\Example\MemoryModelProvider;
use RuntimeException;

class PermissionProvider extends MemoryModelProvider
{
    public const PERMISSION = 'permission';

    /**
     * @return string
     */
    public function type(): string
    {
        return self::PERMISSION;
    }

    /**
     * throws 403 exception
     */
    public function throw403(): void
    {
        throw new RuntimeException('403');
    }

    /**
     * @param string $modelClass
     * @param array $config
     * @return array
     */
    public function findOne(string $modelClass, array $config): array
    {
        $data = [];

        foreach ($config['where'] as $whereConfig) {
            $data[$whereConfig[1]] = $whereConfig[2];
        }

        return $data;
    }
}
