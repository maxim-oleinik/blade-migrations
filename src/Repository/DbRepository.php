<?php namespace Blade\Migrations\Repository;

use Blade\Database\DbAdapter;
use Blade\Migrations\Migration;

/**
 * Доступ к миграциям сохраненным в БД
 */
class DbRepository
{
    /**
     * @var string - DB table name
     */
    private $tableName;

    /**
     * @var DbAdapter
     */
    private $adapter;


    /**
     * Constructor
     *
     * @param string    $tableName
     * @param DbAdapter $db
     */
    public function __construct($tableName, DbAdapter $db)
    {
        $this->adapter = $db;
        $this->tableName = $tableName;
    }


    /**
     * @return DbAdapter
     */
    public function getAdapter(): DbAdapter
    {
        return $this->adapter;
    }


    /**
     * Создать таблицу с Миграциями
     */
    public function install()
    {
        $sql = "
            CREATE TABLE {$this->tableName}
            (
              id serial NOT NULL PRIMARY KEY,
              created_at timestamp (0) NOT NULL DEFAULT now(),
              name VARCHAR(255) NOT NULL,
              data TEXT NOT NULL
            );
        ";

        $this->adapter->execute($sql);
    }


    /**
     * FindByID
     *
     * @param  int $id
     * @return \Blade\Migrations\Migration|null
     */
    public function findById($id)
    {
        $id = (int) $id;
        if ($id) {
            $query = $this->_makeQuery()
                ->filterById($id)
                ->limit(1);
            if ($row = $this->adapter->selectRow($query)) {
                return $this->_makeModel($row);
            }
        }
        return null;
    }


    /**
     * FindLast
     *
     * @return \Blade\Migrations\Migration|null
     */
    public function findLast()
    {
        $query = $this->_makeQuery()
            ->orderBy('id DESC')
            ->limit(1);
        if ($row = $this->adapter->selectRow($query)) {
            return $this->_makeModel($row);
        }

        return null;
    }


    /**
     * Получить список всех Миграций
     *
     * @return Migration[]
     */
    public function all()
    {
        $query = $this->_makeQuery()
            ->orderBy('id DESC');

        $data = $this->adapter->selectAll($query);

        $result = [];
        foreach ($data as $row) {
            $result[] = $this->_makeModel($row);
        }

        return $result;
    }


    /**
     * @param  array $row
     * @return Migration
     */
    private function _makeModel($row): Migration
    {
        $row = array_values((array)$row);
        return new Migration($row[0], $row[1], $row[2]);
    }


    /**
     * Добавить Миграцию в базу
     *
     * @param Migration $migration
     */
    public function insert(Migration $migration)
    {
        $query = $this->_makeQuery()->insert()->values([
            'name' => $migration->getName(),
            'data' => $migration->getSql(),
        ]);
        $this->getAdapter()->execute($query);
    }


    /**
     * Удалить Миграцию из базы
     *
     * @param Migration $migration
     */
    public function delete(Migration $migration)
    {
        if ($migration->isNew()) {
            throw new \InvalidArgumentException(__METHOD__. ': Expected NOT NEW migration');
        }
        $this->adapter->execute((string)$this->_makeQuery()->delete()->filterById($migration->getId()));
    }


    /**
     * @param \Blade\Migrations\Migration $migration
     */
    public function loadSql(Migration $migration)
    {
        $query = $this->_makeQuery()
            ->select('data')
            ->filterByName($migration->getName())
            ->limit(1);

        $data = $this->adapter->selectValue($query);
        if (!$data) {
            throw new \InvalidArgumentException(__METHOD__.": migration `{$migration->getName()}` not found in Database");
        }

        $migration->setSql($data);
    }


    /**
     * Make Query
     */
    private function _makeQuery(): MigrationsQuery
    {
        return MigrationsQuery::make()->from($this->tableName)
            ->select('id, name, created_at');
    }
}
