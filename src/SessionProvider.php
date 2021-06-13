<?php

namespace app\Provider;

use Exception;
use kosuha606\VirtualAdmin\Model\Session;
use kosuha606\VirtualModel\Example\MemoryModelProvider;
use kosuha606\VirtualModel\VirtualModelEntity;

class SessionProvider extends MemoryModelProvider
{
    public const SESSION = 'session';

    public function __construct()
    {
        session_start();
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return self::SESSION;
    }

    /**
     * @param string $modelClass
     * @param array $config
     * @return array
     */
    public function findOne(string $modelClass, array $config): array
    {
        $key = $config['where'][0][2];
        return [
            'id' => 1,
            'key' => $key,
            'value' => $_SESSION[$key] ?? null,
        ];
    }

    public function findMany(string $modelClass, string $config): array
    {
        return [self::findOne($modelClass, $config)];
    }

    /**
     * @return array|int[]
     * @throws Exception
     */
    public function flush(): array
    {
        /** @var VirtualModelEntity $model */
        foreach ($this->persistedModels as $model) {
            if (isset($_SESSION[$model->key])) {
                unset($_SESSION[$model->key]);
            }

            $_SESSION[$model->key] = $model->value;
        }

        return parent::flush();
    }

    /**
     * @param VirtualModelEntity $model
     * @return bool
     */
    public function delete(VirtualModelEntity $model): bool
    {
        /** @var VirtualModelEntity $model */
        unset($_SESSION[$model->key]);

        return true;
    }
}
