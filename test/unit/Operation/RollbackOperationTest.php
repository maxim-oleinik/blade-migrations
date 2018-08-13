<?php namespace Test\Blade\Migrations\Operation;

use Blade\Migrations\Migration;
use Blade\Migrations\Operation\RollbackOperation;
use Blade\Migrations\MigrationService;
use Blade\Migrations\Repository\DbRepository;
use Blade\Migrations\Test\TestLogger;

/**
 * @see \Blade\Migrations\Operation\RollbackOperation
 */
class RollbackOperationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RollbackOperation
     */
    private $cmd;

    /**
     * @var MigrationService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $service;

    /**
     * @var DbRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dbRepository;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->dbRepository = $this->createMock(DbRepository::class);
        $this->service = $this->createMock(MigrationService::class);
        $this->service->expects($this->any())
            ->method('getDbRepository')
            ->will($this->returnValue($this->dbRepository));

        $this->cmd = new RollbackOperation($this->service);

        $this->logger = new TestLogger();
        $this->cmd->setLogger($this->logger);
    }


    /**
     * Нет миграций
     */
    public function testNoMigrations()
    {
        $this->dbRepository->expects($this->once())
            ->method('findLast')
            ->will($this->returnValue(null));

        $this->cmd->run();

        $this->assertSame(['<error>Nothing to rollback</error>'], $this->logger->getLog(), 'Нет ничего');
    }


    /**
     * Не найдена миграция по ID
     */
    public function testMigrationByIdNotFound()
    {
        $this->dbRepository->expects($this->once())
            ->method('findById')
            ->will($this->returnValue(null));

        $this->cmd->run(null, 1);

        $this->assertSame(['<error>Migration with ID=1 not found!</error>'], $this->logger->getLog());
    }


    /**
     * Успешный ролбек с подтверждением
     */
    public function testRollbackSuccessWithConfirmation()
    {
        $this->dbRepository->expects($this->once())
            ->method('findLast')
            ->will($this->returnValue($m1 = new Migration(null, 'M1')));

        $this->service->expects($this->once())
            ->method('down')
            ->with($m1);

        $this->cmd->run(function () { return true; });

        $this->assertSame(['Done'], $this->logger->getLog());
    }


    /**
     * Успешный ролбек с force
     */
    public function testRollbackSuccessWithForce()
    {
        $this->dbRepository->expects($this->once())
            ->method('findLast')
            ->will($this->returnValue($m1 = new Migration(null, 'M1')));

        $this->service->expects($this->once())
            ->method('down')
            ->with($m1);

        $this->cmd->setForce(true);
        $this->cmd->run(function () {
            $this->fail('Excected not called');
        });

        $this->assertSame(['<error>Rollback: M1</error>', 'Done'], $this->logger->getLog());
    }


    /**
     * Успешный ролбек по ID миграции
     */
    public function testRollbackSuccessMigrationId()
    {
        $this->dbRepository->expects($this->once())
            ->method('findById')
            ->will($this->returnValue($m1 = new Migration(null, 'M1')));

        $this->service->expects($this->once())
            ->method('down')
            ->with($m1);

        $this->cmd->run(null, 1);

        $this->assertSame(['Done'], $this->logger->getLog());
    }
}
