<?php namespace Usend\Migrations\Test;

use Usend\Migrations\DbAdapterInterface;
use Usend\Migrations\Migration;
use Usend\Migrations\MigrationsRepository;
use Usend\Migrations\Migrator;


/**
 * @see \Usend\Migrations\Migrator
 *
 * транзакцию
 */
class MigratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MigrationsRepository
     */
    private $repository;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $adapter = new TestDbAdapter;
        $this->repository = new MigrationsRepository('table_name', $adapter);
    }


    /**
     * UP command
     */
    public function testUpCommand()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $migrator->up(new Migration(null, 'migration2.sql'));
        $this->assertEquals(['M2: UP-1', 'M2: UP-2'], $logger->getLog());

        $this->assertEquals([
            'M2: UP-1',
            'M2: UP-2',
            "INSERT INTO table_name (name, sql) VALUES ('migration2.sql', '".trim(file_get_contents(__DIR__.'/fixtures/migration2.sql'))."')",
        ], $this->repository->getAdapter()->log);
    }


    /**
     * Down command
     */
    public function testDownCommand()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $migrator->setLogger($logger = new TestLogger);

        $m = new Migration(2, 'migration2.sql');

        $this->repository->getAdapter()->returnValue = [
            [file_get_contents(__DIR__.'/fixtures/migration2.sql')],
        ];
        $migrator->down($m);
        $this->assertEquals(['M2: DOWN-1', 'M2: DOWN-2'], $logger->getLog());

        $this->assertEquals([
            'M2: DOWN-1',
            'M2: DOWN-2',
            "DELETE FROM table_name WHERE id='2'",
        ], $this->repository->getAdapter()->log);
    }

}
