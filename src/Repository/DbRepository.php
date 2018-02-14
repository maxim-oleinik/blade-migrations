<?php namespace Usend\Migrations\Repository;

use Usend\Migrations\DbAdapterInterface;
use Usend\Migrations\Migration;


class DbRepository
{
    /**
     * @var string - DB table name
     */
    private $tableName;

    /**
     * @var DbAdapterInterface
     */
    private $adapter;


    /**
     * Конструктор
     *
     * @param string             $tableName
     * @param DbAdapterInterface $db
     */
    public function __construct($tableName, DbAdapterInterface $db)
    {
        $this->adapter = $db;
        $this->tableName = $tableName;
    }


    /**
     * @return DbAdapterInterface
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
              created_at timestamp (0) without time zone NOT NULL DEFAULT now(),
              in_transaction INT NOT NULL,
              name character varying (255) NOT NULL,
              down TEXT NOT NULL
            );
        ";

        $this->adapter->execute($sql);
    }


    /**
     * FindByID
     *
     * @param  int $id
     * @return \Usend\Migrations\Migration|null
     */
    public function findById($id)
    {
        $id = (int) $id;
        if ($id) {
            $sql = sprintf("SELECT id, name, created_at FROM {$this->tableName} WHERE id=%d LIMIT 1", $id);
            if ($data = $this->adapter->selectList($sql)) {
                return $this->_make_model(current($data));
            }
        }
        return null;
    }


    /**
     * Получить список всех Миграций
     *
     * @return Migration[]
     */
    public function items($limit = null)
    {
        if ($limit) {
            $limit = ' LIMIT ' . $limit;
        }
        $sql ="SELECT id, name, in_transaction, created_at FROM {$this->tableName} ORDER BY id DESC" . $limit;
        $data = $this->adapter->selectList($sql);

        $result = [];
        foreach ($data as $row) {
            $result[] = $this->_make_model($row);
        }
        // Сортировать по возрастанию
        rsort($result);

        return $result;
    }


    /**
     * @param $row
     * @return \Usend\Migrations\Migration
     */
    private function _make_model($row)
    {
        $row = array_values((array)$row);
        $m = new Migration($row[0], $row[1], $row[3]);
        $m->isTransaction($row[2]);
        return $m;
    }


    /**
     * Добавить Миграцию в базу
     *
     * @param Migration $migration
     */
    public function insert(Migration $migration)
    {
        $sql = sprintf("INSERT INTO {$this->tableName} (name, in_transaction, down) VALUES ('%s', %d, '%s')",
            $this->adapter->escape($migration->getName()),
            $migration->isTransaction()?1:0,
            $this->adapter->escape(implode(";\n", $migration->getDown()))
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
            throw new \InvalidArgumentException(__METHOD__.": Expected NOT NEW migration");
        }
        $this->adapter->execute(sprintf("DELETE FROM {$this->tableName} WHERE id='%d'", $migration->getId()));
    }


    /**
     * @param \Usend\Migrations\Migration $migration
     */
    public function loadSql(Migration $migration)
    {
        $data = $this->adapter->selectList(sprintf("SELECT down FROM {$this->tableName} WHERE name='%s' LIMIT 1", $this->adapter->escape($migration->getName())));
        if (!$data) {
            throw new \InvalidArgumentException(__METHOD__.": migration `{$migration->getName()}` not found DOWN data in Database");
        }

        $row = (array)current($data);
        $migration->setDown(current($row));
    }

}
