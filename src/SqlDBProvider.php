<?php

/** @noinspection PhpUnused */
/** @noinspection SqlResolve */

namespace app\Provider;

use Exception;
use kosuha606\VirtualModel\VirtualModelEntity;
use kosuha606\VirtualModel\VirtualModelProvider;
use LogicException;
use PDO;
use PDOStatement;

class SqlDBProvider extends VirtualModelProvider
{
    private array $modelsToTables;
    private PDO $dbh;

    /**
     * @param array $config
     * @noinspection PhpUnusedParameterInspection
     * @noinspection SqlWithoutWhere
     */
    public function __construct(array $config)
    {
        parent::__construct();
        $this->dbh = new PDO(
            $config['connection']['dsn'],
            $config['connection']['user'],
            $config['connection']['password']
        );
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->modelsToTables = $config['relations'];
        $this->specifyActions([
            'flush' => function() {
                /** @var VirtualModelEntity $model */
                foreach ($this->persistedModels as $model) {
                    $modelClass = get_class($model);
                    $table = $this->getTableByModel($modelClass);
                    $model->isNewRecord = false;
                    $where = '';
                    $isInsert = false;

                    if ($model->hasAttribute('id') && $model->id) {
                        // Обновляем
                        $sql = "UPDATE {$table} SET ";
                        $where = " WHERE id={$model->id}";
                    } else {
                        // Вставляем
                        $sql = "INSERT INTO {$table} SET ";
                        $isInsert = true;
                    }

                    $valuesToSql = [];
                    $prepareValues = [];

                    foreach ($model->getAttributes() as $name => $value) {
                        if (is_numeric($name)) {
                            continue;
                        }

                        if ($value === '' || $value === null) {
                            continue;
                        }

                        $valuesToSql[] = "$name = :$name";
                        $prepareValues[$name] = $value;
                    }

                    $sql .= join(', ', $valuesToSql);
                    $sql .= $where;
                    $statement = $this->dbh->prepare($sql);

                    foreach ($prepareValues as $name => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }

                        if ($name === 'id' && !$value) {
                            $value = null;
                        }

                        $statement->bindValue(":$name", $value);
                    }

                    try {
                        $statement->execute();

                        if ($isInsert) {
                            $id = $this->dbh->lastInsertId($table);
                            $model->id = $id;
                        }
                    } catch (Exception $exception) {
                        $message = $exception->getMessage();
                        $message .= ' | Table: ' . $table;
                        throw new LogicException($message);
                    }
                }

                $this->persistedModels = [];
            },

            'delete' => function(VirtualModelEntity $model): void
            {
                $modelClass = get_class($model);
                $mongoSearch = $this->processQuery(['where' => [['=', 'id', $model->id]]]);
                $table = $this->getTableByModel($modelClass);
                $sql = "DELETE FROM `{$table}` {$mongoSearch['wheresql']}";
                $statement = $this->dbh->prepare($sql);

                foreach ($mongoSearch['prepare_vars'] as $key => $var) {
                    $statement->bindValue($key, $var);
                }

                $statement->execute();
            },

            'deleteByCondition' => function(string $modelClass, array $config)
            {
                throw new LogicException('Not implemented');
            },

            'findOne' => function($modelClass, $config) {
                $mongoSearch = $this->processQuery($config);
                $table = $this->getTableByModel($modelClass);
                $sql = "SELECT * FROM {$table} {$mongoSearch['wheresql']}";

                if (isset($config['sort'])) {
                    $sql .= ' ORDER BY ';
                    $sortFields = [];

                    foreach ($config['sort'] as $key => $value) {
                        $direction = $value ? 'ASC' : 'DESC';
                        $sortFields[] = "$key $direction";
                    }

                    $sql .= join(', ', $sortFields);
                }

                $statement = $this->dbh->prepare($sql);

                foreach ($mongoSearch['prepare_vars'] as $key => $var) {
                    $statement->bindValue($key, $var);
                }

                $statement->execute();

                $result = $this->findPostProcess(
                    $statement->fetchAll()
                );

                if (isset($config['where'])
                    && count($config['where']) > 0
                    && !$mongoSearch['wheresql']) {
                    return [];
                }

                return $result[0] ?? [];
            },

            'findMany' => function($modelClass, $config) {

                $mongoSearch = $this->processQuery($config);
                $table = $this->getTableByModel($modelClass);
                $sql = "SELECT * FROM {$table} {$mongoSearch['wheresql']}";

                if (isset($config['sort'])) {
                    $sql .= ' ORDER BY ';
                    $sortFields = [];

                    foreach ($config['sort'] as $key => $value) {
                        $direction = $value > 1 ? 'ASC' : 'DESC';
                        $sortFields[] = "$key $direction";
                    }

                    $sql .= join(', ', $sortFields);
                }

                $statement = $this->dbh->prepare($sql);

                foreach ($mongoSearch['prepare_vars'] as $key => $var) {
                    $statement->bindValue($key, $var);
                }

                $statement->execute();

                return $this->findPostProcess(
                    $statement->fetchAll()
                );
            },
        ], true);
    }

    /**
     * @param string $sql
     * @return bool
     */
    public function execute(string $sql): bool
    {
        $statement = $this->dbh->prepare($sql);
        return $statement->execute();
    }

    /**
     * @param string $sql
     * @return false|PDOStatement
     */
    public function query(string $sql)
    {
        return $this->dbh->query($sql);
    }

    /**
     * @param string $modelClass
     * @param mixed $config
     */
    public function count(string $modelClass, array $config)
    {

    }

    /**
     * @param $data
     * @return mixed
     */
    private function findPostProcess($data)
    {
        return $data;
    }

    /**
     * @param $config
     * @return array
     */
    private function processQuery($config): array
    {
        $searchConfig = [
            'wheresql' => '',
            'where' => [],
            'prepare_vars' => [],
        ];

        if (isset($config['where'])) {
            foreach ($config['where'] as $whereConfig) {
                if ($whereConfig === 'all') {
                    break;
                }

                if (!is_array($whereConfig) || count($whereConfig) < 3) {
                    continue;
                }

                if ($whereConfig[2] === null) {
                    continue;
                }

                switch ($whereConfig[0]) {
                    case '>':
                        $searchConfig['where'][] = "`{$whereConfig[1]}` > :{$whereConfig[1]}";
                        $searchConfig['prepare_vars'][":{$whereConfig[1]}"] = $whereConfig[2];
                        break;
                    case '<':
                        $searchConfig['where'][] = "`{$whereConfig[1]}` < :{$whereConfig[1]}";
                        $searchConfig['prepare_vars'][":{$whereConfig[1]}"] = $whereConfig[2];
                        break;
                    case '=':
                        $searchConfig['where'][] = "`{$whereConfig[1]}` = :{$whereConfig[1]}";
                        $searchConfig['prepare_vars'][":{$whereConfig[1]}"] = $whereConfig[2];
                        break;
                }
            }
        }

        if ($searchConfig['where']) {
            $searchConfig['wheresql'] = 'WHERE ' . join(' AND ', $searchConfig['where']);
        }

        return $searchConfig;
    }

    /**
     * @param string $modelClass
     * @return string
     */
    private function getTableByModel(string $modelClass): string
    {
        if (!isset($this->modelsToTables[$modelClass])) {
            throw new LogicException("You must register $modelClass in relations config");
        }

        return $this->modelsToTables[$modelClass];
    }

    /**
     * @param $statement
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function executeStatement($statement)
    {
        try {
            $statement->execute();
        } catch (Exception $exception) {
//            $this->lastError = ['exception' => $exception];
        }
    }
}
