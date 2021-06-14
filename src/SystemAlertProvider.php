<?php

namespace app\Provider;

use kosuha606\VirtualModel\Example\MemoryModelProvider;
use kosuha606\VirtualModel\VirtualModelEntity;

class SystemAlertProvider extends MemoryModelProvider
{
    const SYSTEM_ALERT = 'system_alert';
    private string $alertClass;
    private string $sessionClass;

    /**
     * @param string $alertClass
     * @param string $sessionClass
     */
    public function __construct(string $alertClass, string $sessionClass)
    {
        parent::__construct();
        $this->specifyActions([
            'type' => function () {
                return self::SYSTEM_ALERT;
            },

            'loadData' => function () {
                /** @var VirtualModelEntity $session */
                $session = $this->sessionClass;
                $this->memoryStorage[$this->alertClass] = [];
                $data = $session::one(['where' => [['=', 'key', 'alerts']]]);

                if ($data->value) {
                    $this->memoryStorage[$this->alertClass] = json_decode($data->value, true);
                }
            },

            'findMany' => function () {
                if (!$this->memoryStorage) {
                    $this->do('loadData');
                }

                return $this->doParent('findMany', []);
            },

            'flush' => function () {
                /** @var VirtualModelEntity $session */
                $session = $this->sessionClass;
                $data = array_map(function ($item) {
                    return $item->toArray();
                }, $this->persistedModels);

                $session::create([
                    'key' => 'alerts',
                    'value' => json_encode($data, JSON_UNESCAPED_UNICODE),
                ])->save();

                return $this->doParent('flush', []);
            },

            'delete' => function () {
                /** @var VirtualModelEntity $session */
                $session = $this->sessionClass;
                $alerts = $session::one(['where' => [['=', 'key', 'alerts']]]);
                $alerts->delete();

                return true;
            },

        ], true);
        $this->alertClass = $alertClass;
        $this->sessionClass = $sessionClass;
    }
}
