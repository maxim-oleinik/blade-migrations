<?php namespace Usend\Migrations;


class MigrationsRepository
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
              id serial NOT NULL,
              created_at timestamp (0) without time zone NOT NULL DEFAULT now(),
              name character varying (255) NOT NULL,
              sql TEXT NOT NULL
            );
        ";

        $this->adapter->execute($sql);
    }


    /**
     * Получить список всех Миграций
     *
     * @return Migration[]
     */
    public function all()
    {
        $result = [];
        $data = $this->adapter->selectList($sql ="SELECT id, name, created_at FROM {$this->tableName} ORDER BY id");
        foreach ($data as $row) {
            $row = array_values((array)$row);
            $result[] = new Migration($row[0], $row[1], $row[2]);
        }
        return $result;
    }


    /**
     * Добавить Миграцию в базу
     *
     * @param Migration $migration
     */
    public function insert(Migration $migration)
    {
        if (!$migration->getSql()) {
            throw new \InvalidArgumentException(__METHOD__.": Expected migration contains SQL data");
        }

        $sql = sprintf("INSERT INTO {$this->tableName} (name, sql) VALUES ('%s', '%s')",
            $this->adapter->escape($migration->getName()),
            $this->adapter->escape($migration->getSql()));

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
        $data = $this->adapter->selectList(sprintf("SELECT sql FROM {$this->tableName} WHERE name='%s' LIMIT 1", $this->adapter->escape($migration->getName())));
        if (!$data) {
            throw new \InvalidArgumentException(__METHOD__.": migration `{$migration->getName()}` not found DOWN data in Database");
        }

        $row = (array)current($data);
        $migration->setSql(current($row));
    }

}
