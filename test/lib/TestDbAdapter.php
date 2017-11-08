<?php namespace Usend\Migrations\Test;

use Usend\Migrations\DbAdapterInterface;

class TestDbException extends \Exception {}

class TestDbAdapter implements DbAdapterInterface
{
    public $returnValue;
    public $throwExceptionOnCallNum = 0;
    public $log = [];

    public function escape($value)
    {
        return $value;
    }

    public function execute($sql)
    {
        $this->log[] = $sql;
        if ($this->throwExceptionOnCallNum && $this->throwExceptionOnCallNum == count($this->log)) {
            throw new TestDbException('DB exception');
        }
    }

    public function selectList($sql)
    {
        return $this->returnValue;
    }

    public function transaction(callable $func)
    {
        $this->begin();
        try {
            $func();
            $this->commit();
        } catch (TestDbException $e) {
            $this->rollback();
            // Проглатываем наши исключения
            // throw $e;
        }
    }


    public function begin()
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
