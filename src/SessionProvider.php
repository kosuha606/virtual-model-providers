<?php

namespace app\Provider;

use kosuha606\VirtualModel\Example\MemoryModelProvider;
use kosuha606\VirtualModel\VirtualModelEntity;

class SessionProvider extends MemoryModelProvider
{
    public const SESSION = 'session';

    /**
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpUndefinedFieldInspection
     */
    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->specifyActions([
            'type' => function() {
                return self::SESSION;
            },

            'findOne' => function(string $modelClass, array $config) {
                $key = $config['where'][0][2];

                return [
                    'id' => 1,
                    'key' => $key,
                    'value' => $_SESSION[$key] ?? null,
                ];
            },

            'findMany' => function(string $modelClass, string $config) {
                return [$this->do('findOne', [$modelClass, $config])];
            },

            'flush' => function() {
                /** @var VirtualModelEntity $model */
                foreach ($this->persistedModels as $model) {
                    if (isset($_SESSION[$model->key])) {
                        unset($_SESSION[$model->key]);
                    }

                    $_SESSION[$model->key] = $model->value;
                }
            },

            'delete' => function(VirtualModelEntity $model): bool {
                unset($_SESSION[$model->key]);

                return true;
            },
        ], true);
    }
}
