<?php namespace Blade\Migrations\Test;

use \Blade\Migrations\Migration;

/**
 * @see \Blade\Migrations\Migration
 */
class MigrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Ошибки разбора тегов
     */
    public function tagErrors()
    {
        return [
            ['UP tag not found', ''],
            ['UP tag not found', 'SELECT 1'],

            ['DOWN tag not found', '--BEGIN'],
            ['DOWN tag not found', '--UP'],

            ['Expected TRANSACTION tag before UP tag', "--UP\n--TRANSACTION"],

            ['Expected BEGIN/UP tag first', '--DOWN'],
            ['Expected BEGIN/UP tag first', '--ROLLBACK'],

            ['Expected BEGIN/UP tag first', "
                --ROLLBACK
                --BEGIN
            "],

            ['Expected `--DOWN`, got `--ROLLBACK`', "
                --UP
                --ROLLBACK
            "],

            ['Expected `--ROLLBACK`, got `--DOWN`', "
                --BEGIN
                --DOWN
            "],

            ['Expected single --DOWN tag', "
                --UP
                --DOWN
                --DOWN
            "],

            ['Expected single --UP tag', "
                --UP
                --UP
                --DOWN
            "],
        ];
    }

    /**
     * @dataProvider tagErrors
     */
    public function testTagErrors($exceptionMessage, $sql)
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $m->setSql($sql);
    }


    /**
     * Нет инструкций
     */
    public function testEmpty()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --BEGIN
            --ROLLBACK
        ");

        $this->assertSame([], $m->getUp(),'Список запросов для UP');
        $this->assertSame([], $m->getDown(), 'Список запросов для Down');
    }


    /**
     * Всего одна команда
     * - закрывается ;
     * - не закрывается ;
     */
    public function testOneLine()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --BEGIN
            SELECT 1;

            --ROLLBACK
            SELECT 2
        ");

        $this->assertSame(['SELECT 1'], $m->getUp(),'Список запросов для UP');
        $this->assertSame(['SELECT 2'], $m->getDown(), 'Список запросов для Down');
    }


    /**
     * Несколько комманд
     *   - произвольный текст до BEGIN
     *   - пробелы после управляющих тегов
     *   - ; в тексте комманды
     *   - пробельные символы после ;
     */
    public function testMultiLine()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --TRANSACTION
                some text
            --BEGINNER - тег должен завершаться переносом строки
            --BEGIN  
            SELECT ';', 1;

            SELECT 2;
            ;
            ;
            --ROLLBACKCOMMENT
            --ROLLBACK
            SELECT 11; 
            SELECT 22;
        ");

        $this->assertEquals(["SELECT ';', 1", 'SELECT 2', '--ROLLBACKCOMMENT'], $m->getUp(),
            'Список запросов для UP');

        $this->assertEquals(['SELECT 11', 'SELECT 22'], $m->getDown(),
            'Список запросов для Down');

        $this->assertTrue($m->isTransaction(), 'Миграция в Транзакции');
    }


    /**
     * Миграция вне транзакции
     */
    public function testNoTransactionMigration()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --UP
            SELECT 1;
            SELECT 2;

            --DOWN
            SELECT 3
        ");

        $this->assertEquals(["SELECT 1", 'SELECT 2'], $m->getUp(),
            'Список запросов для UP');

        $this->assertEquals(['SELECT 3'], $m->getDown(),
            'Список запросов для Down');

        $this->assertFalse($m->isTransaction(), 'Миграция НЕ в Транзакции');
    }
}
