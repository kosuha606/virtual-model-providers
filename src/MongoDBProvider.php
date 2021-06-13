<?php

namespace app\Provider;

use kosuha606\VirtualModel\VirtualModelEntity;
use kosuha606\VirtualModel\VirtualModelProvider;
use MongoDB\BSON\ObjectId;

/**
 * DOCS
 * https://www.php.net/manual/ru/mongodb.tutorial.library.php
 */
class MongoDBProvider extends VirtualModelProvider
{
    private $dbName = 'trade';

    private $client;

    public function __construct()
    {
        $this->client = new Client("mongodb://localhost:27017");
    }

    /**
     * @throws \Exception
     */
    public function flush()
    {
        /** @var BaseVm $model */
        foreach ($this->persistedModels as $model) {
            $collectionName = $this->getCollectionName($model);
            $model->isNewRecord = false;

            if ($model->hasAttribute('id') && $model->id) {
                $this->getCollection($collectionName)->updateOne(
                    ['_id' => $model->_id],
                    ['$set' => $model->toArray()]
                );
            } else {
                $modelData = $model->toArray();

                foreach (['id', '_id'] as $property) {
                    if (isset($modelData[$property])) {
                        unset($modelData[$property]);
                    }
                }

                $result = $this->getCollection($collectionName)->insertOne($modelData);
                $model->id = (string)$result->getInsertedId();
            }
        }

        $this->persistedModels = [];
    }

    /**
     * @param VirtualModelEntity $model
     * @throws \Exception
     * @throws \Throwable
     */
    public function delete(VirtualModelEntity $model)
    {
        $collectionName = $this->getCollectionName($model);
        $this->getCollection($collectionName)->deleteOne([
            '_id' => $model->_id
        ]);
    }

    /**
     * Удаление моделей по условию
     *
     * @param string $modelClass
     * @param mixed $config
     * @throws \Exception
     */
    public function deleteByCondition($modelClass, $config)
    {

    }

    /**
     * @param string $modelClass
     * @param mixed $config
     */
    public function count($modelClass, $config)
    {

    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return mixed|void
     * @throws \Exception
     */
    protected function findOne($modelClass, $config)
    {
        $mongoSearch = $this->processQuery($config);
        $collectionName = $this->getCollectionName($modelClass);
        $options = [];

        if (isset($config['sort'])) {
            $options['sort'] = $config['sort'];
        }

        $result = (array)$this->getCollection($collectionName)->findOne($mongoSearch, $options);
        $processedResults = $this->findPostProcess([$result]);

        return $processedResults[0] ?? [];
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     * @return mixed|void
     * @throws \Exception
     */
    protected function findMany($modelClass, $config)
    {
        $mongoSearch = $this->processQuery($config);
        $collectionName = $this->getCollectionName($modelClass);
        $options = [];

        if (isset($config['sort'])) {
            $options['sort'] = $config['sort'];
        }

        return $this->findPostProcess($this->getCollection($collectionName)->find($mongoSearch, $options)->toArray());
    }

    private function findPostProcess($data)
    {
        $result = array_map(function($item) {
            if (isset($item['_id']) && $item['_id'] instanceof ObjectId) {
                $item['id'] = (string)$item['_id'];
            }

            return $item;
        }, $data);

        return $result;
    }


    private function processQuery($config)
    {
        $searchConfig = [];

        if (isset($config['where'])) {
            foreach ($config['where'] as $whereConfig) {
                if ($whereConfig === 'all') {
                    break;
                }

                if (!is_array($whereConfig) || count($whereConfig) < 3) {
                    continue;
                }

                switch ($whereConfig[0]) {
                    case '>':
                        $searchConfig[$whereConfig[1]]['$gt'] = $whereConfig[2];
                        break;
                    case '<':
                        $searchConfig[$whereConfig[1]]['$lt'] = $whereConfig[2];
                        break;
                    case '=':
                        if ($whereConfig[1] === 'id') {
                            $searchConfig['_id'] = new ObjectId($whereConfig[2]);
                            break;
                        }

                        $searchConfig[$whereConfig[1]] = $whereConfig[2];
                        break;
                }
            }
        }

        return $searchConfig;
    }

    private function getCollectionName($objectOrClass)
    {
        if (is_object($objectOrClass)) {
            $objectOrClass = get_class($objectOrClass);
        }

        $classParts = array_reverse(explode('/', $objectOrClass));
        $className = reset($classParts);

        return $className;
    }

    private function getCollection($collectionName)
    {
        return $this->client->{$this->dbName}->{$collectionName};
    }
}
