<?php

namespace app\Provider;

use kosuha606\VirtualModel\Example\MemoryModelProvider;
use RuntimeException;

class PermissionProvider extends MemoryModelProvider
{
    /**
     * @return string
     */
    public function type(): string
    {
        return 'permission';
    }

    public function throw403(): void
    {
        throw new RuntimeException('403');
    }

    public function findOne($modelClass, $config)
    {
        $data = [];

        foreach($config['where'] as $whereConfig) {
            $data[$whereConfig[1]] = $whereConfig[2];
        }

        return $data;
    }
}
