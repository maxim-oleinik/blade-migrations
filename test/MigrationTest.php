<?php namespace Usend\Migrations\Test;

use \Usend\Migrations\Migration;

/**
 * @see \Usend\Migrations\Migration
 */
class MigrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Begin тег не найден
     */
    public function testBeginTagNotFound()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UP tag not found');
        $m->setSql('');
    }


    /**
     * Rollback тег не найден
     */
    public function testRollbackTagNotFound()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DOWN tag not found');
        $m->setSql("--BEGIN");
    }

    /**
     * Нет инструкций
     */
    public function testEmpty()
    {
        $m = new Migration(1, 'SomeName', '2017-01-01');
        $m->setSql("
            --BEGIN
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
            --BEGIN
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
                some text
            --BEGINNER - тег должен завершаться переносом строки
            --BEGIN  
            SELECT ';', 1;

            SELECT 2;
            ;
            ;
            --DOWN222
            --DOWN
            SELECT 11; 
            SELECT 22;
        ");

        $this->assertEquals(["SELECT ';', 1", 'SELECT 2', '--DOWN222'], $m->getUp(),
            'Список запросов для UP');

        $this->assertEquals(['SELECT 11', 'SELECT 22'], $m->getDown(),
            'Список запросов для Down');
    }

}
