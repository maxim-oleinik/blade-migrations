<?php namespace Test;


use Migrator;

class MigrateCommandTest extends \PHPUnit_Framework_TestCase
{
    private $db;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->db = $this->createMock(\DbAdapterInterface::class);
    }

    public function testNoMigrations()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures/empty_dir', $this->db);
        $migrator->setLogger($logger = new TestLogger());

        $result = $migrator->migrate();
        $this->assertEquals(['Nothing to migrate'], $logger->getLog());
    }


    public function testNoFreshMigrations()
    {
        // В базе зафиксированы 2 миграции
        $this->db->expects($this->once())
            ->method('select')
            ->will($this->returnValue([['name' => 'migration1.sql'], ['name'=>'migration2.sql']]));

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->db);
        $migrator->setLogger($logger = new TestLogger());

        $result = $migrator->migrate();
        $this->assertEquals(['Nothing to migrate'], $logger->getLog());
    }


    /**
     * UP + DOWN
     */
    public function testMigrateAll()
    {
        $this->db->expects($this->exactly(2))
            ->method('select')
            ->will($this->onConsecutiveCalls(
                [['name' => 'migration1.sql'], ['name'=>'migration3.sql']],
                [['sql' => "--UP\n--DOWN\nM3: DOWN-1"]]
            ));

        $this->db->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(['M3: DOWN-1'], ['M2: UP-1'], ['M2: UP-2']);

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->db);
        $migrator->setLogger($logger = new TestLogger);

        $migrator->migrate();
        $this->assertEquals(['M3: DOWN-1', 'M2: UP-1', 'M2: UP-2'], $logger->getLog());
    }

}
