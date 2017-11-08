<?php namespace Usend\Migrations\Test;

use Usend\Migrations\Migration;
use Usend\Migrations\MigrationsRepository;
use Usend\Migrations\Migrator;


/**
 * @see \Usend\Migrations\Migrator
 */
class MigratorTest extends \PHPUnit_Framework_TestCase
{
    private $repository;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->repository = $this->createMock(MigrationsRepository::class);
    }


    /**
     * UP command
     */
    public function testUpCommand()
    {
        $this->markTestIncomplete();

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $this->repository->expects($this->once())
            ->method('all')
            ->will($this->returnValue([]));

        $migrator->up(new Migration(null, 'migration2.sql', null));
        $this->assertEquals(['M2: UP-1', 'M2: UP-2'], $logger->getLog());
    }


    /**
     * Down command
     */
    public function testDownCommand()
    {
        $this->markTestIncomplete();

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $this->repository->expects($this->once())
            ->method('all')
            ->will($this->returnValue([]));

        $m = new Migration(2, 'migration2.sql', null);
        $m->setSql(file_get_contents(__DIR__ . '/fixtures/migration2.sql'));

        $migrator->down($m);
        $this->assertEquals(['M2: DOWN-1', 'M2: DOWN-2'], $logger->getLog());
    }

}
