<?php

namespace app\Provider;

use app\Domain\Base\BaseVm;
use kosuha606\VirtualModel\VirtualModelEntity;
use kosuha606\VirtualModel\VirtualModelProvider;
use LogicException;
use PDO;

class SqlDBProvider extends VirtualModelProvider
{
    private array $modelsToTables = [];
    private PDO $dbh;

    public function __construct(array $config)
    {
        $this->dbh = new PDO(
            $config['connection']['dsn'],
            $config['connection']['user'],
            $config['connection']['password']
        );
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->modelsToTables = $config['relations'];
    }

    public function execute(string $sql): bool
    {
        $statement = $this->dbh->prepare($sql);
        return $statement->execute();
    }

    public function query(string $sql)
    {
        return $this->dbh->query($sql);
    }

    /**
     * @throws \Exception
     */
    public function flush()
    {
        /** @var BaseVm $model */
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
            } catch (\Exception $exception) {
                $message = $exception->getMessage();
                $message .= ' | Table: '.$table;
                throw new \LogicException($message);
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
        $modelClass = get_class($model);
        $mongoSearch = $this->processQuery(['where' => [['=', 'id', $model->id]]]);
        $table = $this->getTableByModel($modelClass);
        $sql = "DELETE FROM `{$table}` {$mongoSearch['wheresql']}";
        $statement = $this->dbh->prepare($sql);

        foreach ($mongoSearch['prepare_vars'] as $key => $var) {
            $statement->bindValue($key, $var);
        }

        $statement->execute();
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
    }

    private function findPostProcess($data)
    {
        return $data;
    }

    private function processQuery($config)
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

    private function executeStatement($statement)
    {
        try {
            $statement->execute();
        } catch (\Exception $exception) {

        }
    }
}
