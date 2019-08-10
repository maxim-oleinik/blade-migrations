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
    public function getAdapter()
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
            $sql = sprintf("SELECT id, name, created_at FROM {$this->tableName} WHERE id=%d LIMIT 1", $id);
            if ($row = $this->adapter->selectRow($sql)) {
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
        $sql = sprintf("SELECT id, name, created_at FROM {$this->tableName} ORDER BY id DESC LIMIT 1");
        if ($row = $this->adapter->selectRow($sql)) {
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
        $sql ="SELECT id, name, created_at FROM {$this->tableName} ORDER BY id DESC";
        $data = $this->adapter->selectAll($sql);

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
        $sql = sprintf("INSERT INTO {$this->tableName} (name, data) VALUES ('%s', '%s')",
            $this->adapter->escape($migration->getName()),
            $this->adapter->escape($migration->getSql())
        );
        $this->getAdapter()->execute($sql);
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
        $this->adapter->execute(sprintf("DELETE FROM {$this->tableName} WHERE id='%d'", $migration->getId()));
    }


    /**
     * @param \Blade\Migrations\Migration $migration
     */
    public function loadSql(Migration $migration)
    {
        $data = $this->adapter->selectValue(sprintf("SELECT data FROM {$this->tableName} WHERE name='%s' LIMIT 1", $this->adapter->escape($migration->getName())));
        if (!$data) {
            throw new \InvalidArgumentException(__METHOD__.": migration `{$migration->getName()}` not found in Database");
        }

        $migration->setSql($data);
    }
}
