<?php

namespace app\Provider;

use kosuha606\VirtualAdmin\Model\Session;
use kosuha606\VirtualModel\Example\MemoryModelProvider;
use kosuha606\VirtualModel\VirtualModelEntity;

class SessionProvider extends MemoryModelProvider
{
    public function __construct()
    {
        session_start();
    }

    public function type()
    {
        return Session::TYPE;
    }

    public function findOne($modelClass, $config)
    {
        $key = $config['where'][0][2];
        return [
            'id' => 1,
            'key' => $key,
            'value' => $_SESSION[$key] ?? null,
        ];
    }

    public function findMany($modelClass, $config)
    {
        return [self::findOne($modelClass, $config)];
    }

    public function flush()
    {
        /** @var Session $model */
        foreach ($this->persistedModels as $model) {
            if (isset($_SESSION[$model->key])) {
                unset($_SESSION[$model->key]);
            }

            $_SESSION[$model->key] = $model->value;
        }

        return parent::flush();
    }

    public function delete(VirtualModelEntity $model)
    {
        /** @var Session $model */
        unset($_SESSION[$model->key]);

        return true;
    }
}
