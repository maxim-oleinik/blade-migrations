<?php namespace Usend\Migrations\Test;

use Usend\Migrations\Migration;
use Usend\Migrations\Repository\DbRepository;
use Usend\Migrations\MigrationService;
use Usend\Migrations\Repository\FileRepository;


/**
 * @see \Usend\Migrations\MigrationService
 */
class MigrateUpDownTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbRepository
     */
    private $repository;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $adapter = new TestDbAdapter;
        $this->repository = new DbRepository('table_name', $adapter);
    }


    /**
     * UP command
     */
    public function testUpCommand()
    {
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $migrator->up(new Migration(null, 'migration2.sql'));
        $this->assertEquals(["M2: UP-1\n", "M2: UP-2\n"], $logger->getLog());

        $this->assertEquals([
            'BEGIN',
            'M2: UP-1',
            'M2: UP-2',
            "INSERT INTO table_name (name, in_transaction, down) VALUES ('migration2.sql', 1, 'M2: DOWN-1;\nM2: DOWN-2')",
            'COMMIT',
        ], $this->repository->getAdapter()->log);
    }


    /**
     * UP без транзакции
     */
    public function testUpNoTransaction()
    {
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $migrator->up(new Migration(null, 'migration-no-trans.sql'));
        $this->assertEquals(["M3: UP\n"], $logger->getLog());

        $this->assertEquals([
            'M3: UP',
            "INSERT INTO table_name (name, in_transaction, down) VALUES ('migration-no-trans.sql', 0, 'M3: DOWN')",
        ], $this->repository->getAdapter()->log);
    }


    /**
     * UP: Исключение в транзакции
     */
    public function testUpException()
    {
        $adapter = new TestDbAdapter;
        $adapter->throwExceptionOnCallNum = 3;

        $this->repository = new DbRepository('table_name', $adapter);
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);

        $migrator->up(new Migration(null, 'migration2.sql'));

        $this->assertEquals([
            'BEGIN',
            'M2: UP-1',
            'M2: UP-2',
            'ROLLBACK',
        ], $this->repository->getAdapter()->log);
    }


    /**
     * Down command
     */
    public function testDownCommand()
    {
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $m = new Migration(2, 'migration2.sql');

        $this->repository->getAdapter()->returnValue = [
            ['M2: DOWN'],
        ];
        $migrator->down($m);
        $this->assertEquals(["M2: DOWN\n"], $logger->getLog());

        $this->assertEquals([
            'BEGIN',
            'M2: DOWN',
            "DELETE FROM table_name WHERE id='2'",
            'COMMIT',
        ], $this->repository->getAdapter()->log);
    }


    /**
     * Down без транзакции
     */
    public function testDownNoTransaction()
    {
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $m = new Migration(2, 'migration-no-trans.sql');
        $m->isTransaction(false);

        $this->repository->getAdapter()->returnValue = [
            ['M3: DOWN'],
        ];
        $migrator->down($m);
        $this->assertEquals(["M3: DOWN\n"], $logger->getLog());

        $this->assertEquals([
            'M3: DOWN',
            "DELETE FROM table_name WHERE id='2'",
        ], $this->repository->getAdapter()->log);
    }
}
