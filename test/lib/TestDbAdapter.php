<?php namespace Usend\Migrations\Test;

use Usend\Migrations\DbAdapterInterface;


class TestDbAdapter implements DbAdapterInterface
{
    public $returnValue;
    public $log = [];

    public function escape($value)
    {
        return $value;
    }

    public function execute($sql)
    {
        $this->log[] = $sql;
    }

    public function selectList($sql)
    {
        return $this->returnValue;
    }
}
