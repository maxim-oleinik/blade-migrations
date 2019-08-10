<?php namespace Test\Blade\Migrations;

use \Blade\Migrations\Migration;

/**
 * @see \Blade\Migrations\Migration
 */
class MigrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Ошибки разбора тегов
     */
    public function tagErrors()
    {
        return [
            ['UP tag not found', ''],
            ['UP tag not found', 'SELECT 1'],

            ['DOWN tag not found', '--UP'],

            ['Expected TRANSACTION tag before UP tag', "--UP\n--TRANSACTION"],

            ['Expected UP tag first', '--DOWN'],

            ['Expected UP tag first', "
                --DOWN
                --UP
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

            ['Expected SEPARATOR tag before UP tag', "--UP\n--SEPARATOR=@"],
            ['Expected SEPARATOR tag has value', "--SEPARATOR"],
            ['Expected SEPARATOR tag has value', "--SEPARATOR\n--UP"],
            ['Expected SEPARATOR tag has value', "--SEPARATOR=\n--UP"],
            ['Expected SEPARATOR tag has value', "--SEPARATOR= \n--UP"],
            ['Expected SEPARATOR tag has SINGLE CHAR value', "--SEPARATOR=AB"],
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
            --UP
            --DOWN
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
            --UP
            SELECT 1;

            --DOWN
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
            --UPNER - тег должен завершаться переносом строки
            --UP  
            SELECT ';', 1;

            SELECT 2;
            ;
            ;
            --DOWNCOMMENT
            --DOWN
            SELECT 11; 
            SELECT 22;
        ");

        $this->assertEquals(["SELECT ';', 1", 'SELECT 2', '--DOWNCOMMENT'], $m->getUp(),
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


    /**
     * Альтернативный разделитель
     */
    public function testCustomSeparator()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --SEPARATOR=\
            --UP
            SELECT 1\
            SELECT 2\

            --DOWN
            SELECT 3\
            SELECT 4\
            \\\\
        ");

        $this->assertEquals(["SELECT 1", 'SELECT 2'], $m->getUp(),
            'Список запросов для UP');

        $this->assertEquals(['SELECT 3', 'SELECT 4'], $m->getDown(),
            'Список запросов для Down');
    }
}
