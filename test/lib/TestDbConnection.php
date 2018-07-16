<?php namespace Blade\Migrations\Test;

use Blade\Database\DbConnectionInterface;

class TestDbException extends \Exception {}

class TestDbConnection implements DbConnectionInterface
{
    public $returnValue;
    public $throwExceptionOnCallNum = 0;
    public $log = [];

    public function escape($value): string
    {
        return (string)$value;
    }

    public function execute($sql, $bindings = []): int
    {
        $this->log[] = $sql;
        if ($this->throwExceptionOnCallNum && $this->throwExceptionOnCallNum == count($this->log)) {
            throw new TestDbException('DB exception');
        }
        return 1;
    }

    public function each($sql, $bindings = [], callable $callback)
    {
        if ($this->returnValue) {
            foreach ($this->returnValue as $row) {
                $callback($row);
            }
        }
    }

    public function beginTransaction()
    {
        $this->execute('BEGIN');
    }

    public function commit()
    {
        $this->execute('COMMIT');
    }

    public function rollback()
    {
        $this->execute('ROLLBACK');
    }
}
