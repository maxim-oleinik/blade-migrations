<?php namespace Usend\Migrations\Test;

use Usend\Migrations\Migration;
use Usend\Migrations\MigrationsRepository;
use Usend\Migrations\Migrator;

/**
 * @see \Usend\Migrations\Migrator
 */
class MigrateCommandTest extends \PHPUnit_Framework_TestCase
{
    private $repository;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->repository = $this->createMock(MigrationsRepository::class);
    }


    public function testNoMigrations()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger());

        $this->repository->expects($this->once())
            ->method('items')
            ->will($this->returnValue([]));

        $result = $migrator->migrate();
        $this->assertEquals(['Nothing to migrate'], $logger->getLog());
    }


    public function testNoFreshMigrations()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger());

        // В базе зафиксированы 2 миграции
        $this->repository->expects($this->once())
            ->method('items')
            ->will($this->returnValue([
                new Migration(1, 'migration1.sql', null),
                new Migration(2, 'migration2.sql', null),
            ]));

        $result = $migrator->migrate();
        $this->assertEquals(['Nothing to migrate'], $logger->getLog());
    }


    /**
     * UP + DOWN
     */
    public function testMigrateAll()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger());

        // В базе зафиксированы 2 миграции
        $this->repository->expects($this->once())
            ->method('items')
            ->will($this->returnValue([
                new Migration(1, 'migration1.sql', null),
                new Migration(3, 'migration3.sql', null),
            ]));

        $migrator->migrate();
        $this->assertEquals(['M3: DOWN-1', 'M2: UP-1', 'M2: UP-2'], $logger->getLog());
    }

}
