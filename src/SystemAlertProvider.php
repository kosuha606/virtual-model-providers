<?php

namespace app\Provider;

use Exception;
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
        $this->alertClass = $alertClass;
        $this->sessionClass = $sessionClass;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return self::SYSTEM_ALERT;
    }

    /**
     * @throws Exception
     */
    public function loadData(): void
    {
        /** @var VirtualModelEntity $session */
        $session = $this->sessionClass;
        $this->memoryStorage[$this->alertClass] = [];
        $data = $session::one(['where' => [['=', 'key', 'alerts']]]);

        if ($data->value) {
            $this->memoryStorage[$this->alertClass] = json_decode($data->value, true);
        }
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return array|mixed
     * @throws Exception
     */
    protected function findMany(string $modelClass, array $config): array
    {
        if (!$this->memoryStorage) {
            $this->loadData();
        }

        return parent::findMany($modelClass, $config);
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function flush(): ?array
    {
        /** @var VirtualModelEntity $session */
        $session = $this->sessionClass;
        $data = array_map(function($item) {
            return $item->toArray();
        }, $this->persistedModels);

        $session::create([
            'key' => 'alerts',
            'value' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ])->save();

        return parent::flush();
    }

    /**
     * @param VirtualModelEntity $model
     * @return bool
     * @throws Exception
     */
    public function delete(VirtualModelEntity $model): bool
    {
        /** @var VirtualModelEntity $session */
        $session = $this->sessionClass;
        $alerts = $session::one(['where' => [['=', 'key', 'alerts']]]);
        $alerts->delete();

        return true;
    }
}
