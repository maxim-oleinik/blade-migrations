<?php namespace Test\Blade\Migrations;

use Blade\Database\DbAdapter;
use Blade\Migrations\Migration;
use Blade\Migrations\Repository\DbRepository;
use Blade\Migrations\MigrationService;
use Blade\Migrations\Repository\FileRepository;
use Blade\Migrations\Test\TestDbConnection;
use Blade\Migrations\Test\TestDbException;
use Blade\Migrations\Test\TestLogger;

/**
 * @see \Blade\Migrations\MigrationService
 */
class MigrateUpDownTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DbRepository
     */
    private $dbRepository;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * @var MigrationService
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
        $adapter = new DbAdapter(new TestDbConnection());
        $this->dbRepository = new DbRepository('table_name', $adapter);
        $this->fileRepository = new FileRepository(__DIR__ . '/../fixtures');
        $this->service = new MigrationService($this->fileRepository, $this->dbRepository);
        $this->service->setLogger($this->logger = new TestLogger);
    }

    private function _getMigrationData($fileName)
    {
        return trim(file_get_contents(__DIR__ . '/../fixtures/' . $fileName));
    }

    /**
     * UP command
     */
    public function testUpCommand()
    {
        $this->service->up(new Migration(null, 'migration2.sql'));
        $this->assertEquals(["M2: UP-1\n", "M2: UP-2\n"], $this->logger->getLog());

        $this->assertEquals([
            'BEGIN',
            'M2: UP-1',
            'M2: UP-2',
            "INSERT INTO table_name (name, data) VALUES ('migration2.sql', '".$this->_getMigrationData('migration2.sql')."')",
            'COMMIT',
        ], $this->dbRepository->getAdapter()->getConnection()->log);
    }

    /**
     * UP with ROLLBACK command
     */
    public function testUpWithRollbackCommand()
    {
        $this->service->up(new Migration(null, 'migration2.sql'), true);
        $this->assertEquals([
            "M2: UP-1\n",
            "M2: UP-2\n",
            "<comment>Rollback</comment>",
            "<comment>----------------------------------</comment>",
            "M2: DOWN-1\n",
            "M2: DOWN-2\n",
            "<comment>----------------------------------</comment>",
            "M2: UP-1\n",
            "M2: UP-2\n",
        ], $this->logger->getLog());

        $this->assertEquals([
            'BEGIN',
            'M2: UP-1',
            'M2: UP-2',
            "M2: DOWN-1",
            "M2: DOWN-2",
            'M2: UP-1',
            'M2: UP-2',
            "INSERT INTO table_name (name, data) VALUES ('migration2.sql', '".$this->_getMigrationData('migration2.sql')."')",
            'COMMIT',
        ], $this->dbRepository->getAdapter()->getConnection()->log);
    }


    /**
     * UP без транзакции
     */
    public function testUpNoTransaction()
    {
        $this->service->up(new Migration(null, 'migration-no-trans.sql'));
        $this->assertEquals(['NO TRANSACTION!',"M3: UP\n"], $this->logger->getLog());

        $this->assertEquals([
            'M3: UP',
            "INSERT INTO table_name (name, data) VALUES ('migration-no-trans.sql', '".$this->_getMigrationData('migration-no-trans.sql')."')",
        ], $this->dbRepository->getAdapter()->getConnection()->log);
    }


    /**
     * UP: Исключение в транзакции
     */
    public function testUpException()
    {
        $adapter = new DbAdapter($con = new TestDbConnection());
        $con->throwExceptionOnCallNum = 3;

        $this->dbRepository = new DbRepository('table_name', $adapter);
        $migrator = new MigrationService($this->fileRepository, $this->dbRepository);

        try {
            $migrator->up(new Migration(null, 'migration2.sql'));
            $this->fail('Expected exception');
        } catch (TestDbException $e) {
        }

        $this->assertEquals([
            'BEGIN',
            'M2: UP-1',
            'M2: UP-2',
            'ROLLBACK',
        ], $this->dbRepository->getAdapter()->getConnection()->log);
    }


    /**
     * Down command
     */
    public function testDownCommand()
    {
        $m = new Migration(2, 'migration2.sql');

        $this->dbRepository->getAdapter()->getConnection()->returnValue = [
            ['data'=>$this->_getMigrationData('migration2.sql')],
        ];
        $this->service->down($m);
        $this->assertEquals(["M2: DOWN-1\n", "M2: DOWN-2\n"], $this->logger->getLog());

        $this->assertEquals([
            'BEGIN',
            'M2: DOWN-1',
            'M2: DOWN-2',
            "DELETE FROM table_name WHERE id='2'",
            'COMMIT',
        ], $this->dbRepository->getAdapter()->getConnection()->log);
    }


    /**
     * Down без транзакции
     */
    public function testDownNoTransaction()
    {
        $m = new Migration(2, 'migration-no-trans.sql');
        $m->isTransaction(false);

        $this->dbRepository->getAdapter()->getConnection()->returnValue = [
            ['data'=>$this->_getMigrationData('migration-no-trans.sql')],
        ];
        $this->service->down($m);
        $this->assertEquals(['NO TRANSACTION!', "M3: DOWN\n"], $this->logger->getLog());

        $this->assertEquals([
            'M3: DOWN',
            "DELETE FROM table_name WHERE id='2'",
        ], $this->dbRepository->getAdapter()->getConnection()->log);
    }
}
