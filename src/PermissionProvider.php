<?php

namespace app\Provider;

use kosuha606\VirtualModel\Example\MemoryModelProvider;
use RuntimeException;

class PermissionProvider extends MemoryModelProvider
{
    public const PERMISSION = 'permission';

    public function __construct()
    {
        parent::__construct();
        $this->specifyActions([
            'type' => function() {
                return self::PERMISSION;
            },

            'throw403' => function() {
                throw new RuntimeException('403');
            },

            'findOne' => function(string $modelClass, array $config) {
                $data = [];

                foreach ($config['where'] as $whereConfig) {
                    $data[$whereConfig[1]] = $whereConfig[2];
                }

                return $data;
            },
        ], true);
    }
}
