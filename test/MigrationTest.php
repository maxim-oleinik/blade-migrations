<?php namespace Usend\Migrations\Test;

use \Usend\Migrations\Migration;

/**
 * @see \Usend\Migrations\Migration
 */
class MigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateSimpleMigration()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --UP
            SELECT 1;
            SELECT 2;
            ;
            ;
            --DOWN
            SELECT 11;
            SELECT 22;
        ");

        $this->assertEquals(['SELECT 1', 'SELECT 2'], $m->getUp(),
            'Список запросов для UP');

        $this->assertEquals(['SELECT 11', 'SELECT 22'], $m->getDown(),
            'Список запросов для Down');
    }

}
