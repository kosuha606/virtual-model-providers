<?php

namespace kosuha606\VirtualModelProviders;

use Exception;
use kosuha606\VirtualModel\VirtualModelEntity;
use kosuha606\VirtualModel\VirtualModelProvider;
use LogicException;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use Throwable;

/**
 * DOCS
 * https://www.php.net/manual/ru/mongodb.tutorial.library.php
 */
class MongoDBProvider extends VirtualModelProvider
{
    public const ALL_CONDITION = 'all';
    public const NOT_IMPLEMENTED = 'Not implemented';

    private string $dbName;
    private Client $client;

    /**
     * @param string $dbName
     * @param string $dsn mongodb://localhost:27017
     */
    public function __construct(string $dbName, string $dsn)
    {
        parent::__construct();
        $this->client = new Client($dsn);
        $this->dbName = $dbName;
        $this->specifyActions([
            'flush' => function () {
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
            },

            'delete' => function (VirtualModelEntity $model) {
                $collectionName = $this->getCollectionName($model);
                $this->getCollection($collectionName)->deleteOne([
                    '_id' => $model->_id
                ]);
            },

            'deleteByCondition' => function (string $modelClass, $config) {
                throw new LogicException(self::NOT_IMPLEMENTED . $modelClass . json_decode($config));
            },

            'count' => function (string $modelClass, $config) {
                throw new LogicException(self::NOT_IMPLEMENTED . $modelClass . json_encode($config));
            },

            'findOne' => function ($modelClass, $config): array {
                $mongoSearch = $this->processQuery($config);
                $collectionName = $this->getCollectionName($modelClass);
                $options = [];

                if (isset($config['sort'])) {
                    $options['sort'] = $config['sort'];
                }

                $result = (array)$this->getCollection($collectionName)->findOne($mongoSearch, $options);
                $processedResults = $this->findPostProcess([$result]);

                return $processedResults[0] ?? [];
            },

            'findMany' => function ($modelClass, $config): array {
                $mongoSearch = $this->processQuery($config);
                $collectionName = $this->getCollectionName($modelClass);
                $options = [];

                if (isset($config['sort'])) {
                    $options['sort'] = $config['sort'];
                }

                return $this->findPostProcess($this->getCollection($collectionName)->find($mongoSearch, $options)->toArray());
            },


        ], true);
    }

    /**
     * @param $data
     * @return array
     */
    private function findPostProcess($data): array
    {
        return array_map(function ($item) {
            if (isset($item['_id']) && $item['_id'] instanceof ObjectId) {
                $item['id'] = (string)$item['_id'];
            }

            return $item;
        }, $data);
    }

    /**
     * @param $config
     * @return array
     */
    private function processQuery($config): array
    {
        $searchConfig = [];

        if (isset($config['where'])) {
            foreach ($config['where'] as $whereConfig) {
                if ($whereConfig === self::ALL_CONDITION) {
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

    /**
     * @param $objectOrClass
     * @return mixed
     */
    private function getCollectionName($objectOrClass)
    {
        if (is_object($objectOrClass)) {
            $objectOrClass = get_class($objectOrClass);
        }

        $classParts = array_reverse(explode('/', $objectOrClass));

        return reset($classParts);
    }

    /**
     * @param $collectionName
     * @return Collection
     */
    private function getCollection($collectionName): Collection
    {
        return $this->client->{$this->dbName}->{$collectionName};
    }
}
