<?php namespace Test\Blade\Migrations\Operation;

use Blade\Migrations\Operation\MigrateOperation;
use Blade\Migrations\Migration;
use Blade\Migrations\MigrationService;
use Blade\Migrations\Test\TestLogger;

/**
 * @see \Blade\Migrations\Operation\MigrateOperation
 */
class MigrateOperationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MigrateOperation
     */
    private $cmd;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $service;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->service = $this->createMock(MigrationService::class);
        $this->logger = new TestLogger();

        $this->cmd = new MigrateOperation($this->service);
        $this->cmd->setLogger($this->logger);
    }


    /**
     * Нет миграций
     */
    public function testNoMigrations()
    {
        $this->service
            ->expects($this->once())
            ->method('getDiff')
            ->willReturn([]);

        $this->cmd->run();

        $this->assertSame(['<error>Nothing to migrate</error>'], $this->logger->getLog(), 'Нет ничего');
    }


    /**
     * Пользователь отказался
     */
    public function testCallbackReject()
    {
        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([
                $m1 = new Migration(null, 'M1'),
            ]);

        $this->service->expects($this->never())
            ->method('up');

        $this->cmd->run(static function () {
            // Отказ
            return false;
        });

        $this->assertSame([], $this->logger->getLog());
    }


    /**
     * есть 2 миграции вверх - выполняем только первую
     */
    public function testTwoMigrationsUpRunFirstOnly()
    {
        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([
                $m1 = new Migration(null, 'M1'),
                new Migration(null, 'M2'),
            ]);

        $this->service->expects($this->once())
            ->method('up')
            ->with($m1);

        $this->cmd->run(static function () {
            return true;
        });

        $this->assertSame(['Done'], $this->logger->getLog());
    }


    /**
     * UP-DOWN-UP - автотест миграции
     */
    public function testUpDownUp()
    {
        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([
                $m1 = new Migration(null, 'M1'),
            ]);

        $this->service->expects($this->once())
            ->method('up')
            ->with($m1);

        $this->cmd->setTestRollback(true);
        $this->cmd->run();

        $this->assertSame([
            '<info>M1</info>',
            '<error>WITH Rollback</error>',
            'Done'
        ], $this->logger->getLog());
    }


    /**
     * есть 2 миграции вверх - Вторая указана явно
     */
    public function testTwoMigrationsUpRunSecondOnDemand()
    {
        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([
                $m1 = new Migration(null, 'M1'),
                $m2 = new Migration(null, 'M2'),
            ]);

        $this->service->expects($this->once())
            ->method('up')
            ->with($m2);

        $this->cmd->run(null, $m2->getName());

        $this->assertSame(['<info>M2</info>', 'Done'], $this->logger->getLog());
    }


    /**
     * Явно указанная миграция не найдена
     */
    public function testUpSelectedMigrationNotFound()
    {
        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([]);

        $this->service->expects($this->never())->method('up');

        $this->cmd->run(null, 'UnknownMigration');

        $this->assertSame(["<error>Migration `UnknownMigration` not found or applied already</error>"], $this->logger->getLog());
    }


    /**
     * Autho: есть 2 миграции вверх - выполняем обе
     */
    public function testAutoTwoMigrationsUpRunAll()
    {
        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([
                $m1 = new Migration(null, 'M1'),
                $m2 = new Migration(null, 'M2'),
            ]);

        $this->service->expects($this->exactly(2))
            ->method('up')
            ->withConsecutive($m1, $m2);

        // Все миграции
        $this->cmd->setAuto(true);
        $this->cmd->run();

        $this->assertSame(['<info>M1</info>','<info>M2</info>','Done'], $this->logger->getLog());
    }


    /**
     * Auto: Автооткат отдной миграции
     */
    public function testAutoRollbackOne()
    {
        $m1 = new Migration(1, 'M1');
        // Миграция на удаление
        $m1->isRemove(true);

        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([$m1]);

        $this->service->expects($this->once())
            ->method('down')
            ->with($m1);

        $this->service->expects($this->never())
            ->method('up');

        // Все миграции
        $this->cmd->setAuto(true);
        $this->cmd->run();

        $this->assertSame(['<error>Rollback: M1</error>', 'Done'], $this->logger->getLog());
    }


    /**
     * Auto: Добавить одну, удалить вторую
     */
    public function testAutoAddFirstRollbackSecond()
    {
        $m1 = new Migration(null, 'M1');
        $m2 = new Migration(2, 'M2');
        $m2->isRemove(true);

        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([$m1, $m2]);

        $this->service->expects($this->once())
            ->method('up')
            ->with($m1);

        $this->service->expects($this->once())
            ->method('down')
            ->with($m2);

        // Все миграции
        $this->cmd->setAuto(true);
        $this->cmd->run();

        $this->assertSame(['<info>M1</info>', '<error>Rollback: M2</error>', 'Done'], $this->logger->getLog());
    }


    /**
     * Auto: игнорировать проверку ролбека, если указано --auto
     */
    public function testAutoIgnoreTestRollback()
    {
        $m1 = new Migration(null, 'M1');

        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([$m1]);

        $this->service->expects($this->once())
            ->method('up')
            ->with($m1);

        $this->service->expects($this->never())
            ->method('down');

        // Все миграции
        $this->cmd->setAuto(true);
        $this->cmd->setTestRollback(true);
        $this->cmd->run();

        $this->assertSame(['<error>Ignore "Test Rollback" option with "Auto" option</error>', '<info>M1</info>', 'Done'], $this->logger->getLog());
    }


    /**
     * Форс - Колбек не вызывается
     */
    public function testForceNoCallback()
    {
        // Force
        $this->cmd->setForce(true);
        $this->cmd->setAuto(true);

        $m1 = new Migration(null, 'M1');
        $m2 = new Migration(2, 'M2');
        $m2->isRemove(true);

        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([$m2, $m1]);

        $this->cmd->run(function () {
            $this->fail('Expected no call');
        });

        $this->assertSame(['<error>Rollback: M2</error>', '<info>M1</info>', 'Done'], $this->logger->getLog());
    }


    /**
     * Запуск без логгера
     */
    public function testNoLogger()
    {
        $this->service = $this->createMock(MigrationService::class);
        $this->cmd = new MigrateOperation($this->service);

        $m1 = new Migration(null, 'M1');

        $this->service->expects($this->once())
            ->method('getDiff')
            ->willReturn([$m1]);

        $this->service->expects($this->once())
            ->method('up')
            ->with($m1);

        $this->cmd->run();
    }
}
