<?php namespace Test;

use Migrator;

class MigratorTest extends \PHPUnit_Framework_TestCase
{
    private $db;


    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->db = $this->createMock(\DbAdapterInterface::class);
    }


    /**
     * Статус - нет миграций
     */
    public function testStatusNoMigrations()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures/empty_dir', $this->db);
        $result = $migrator->status();
        $this->assertSame([
            'up'=>[],
            'down'=>[],
            'current'=>[],
        ], $result, 'Нет ничего');
    }


    /**
     * Статус - миграции UP
     */
    public function testStatusUp()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->db);
        $result = $migrator->status();
        $this->assertSame([
            'up'=>[
                'migration1.sql',
                'migration2.sql',
            ],
            'down'=>[],
            'current'=>[],
        ], $result);
    }


    /**
     * Статус - все миграции - Up, Down, Applied
     */
    public function testStatusAll()
    {
        // В базе зафиксированы 2 миграции
        $this->db->expects($this->once())
            ->method('select')
            ->will($this->returnValue([['name' => 'migration1.sql'], ['name'=>'migration3.sql']]));

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->db);
        $result = $migrator->status();
        // Накатить М2 и откатить М3
        $this->assertSame([
            'up'=>[
                'migration2.sql',
            ],
            'down'=>[
                'migration3.sql',
            ],
            'current'=>[
                'migration1.sql',
            ],
        ], $result);
    }


    /**
     * UP command
     */
    public function testUpCommand()
    {
        $this->db->expects($this->exactly(2))
           ->method('execute')
            ->withConsecutive(['M2: UP-1'], ['M2: UP-2']);

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->db);
        $migrator->setLogger($logger = new TestLogger);

        $migrator->up('migration2.sql');
        $this->assertEquals(['M2: UP-1', 'M2: UP-2'], $logger->getLog());
    }


    /**
     * Down command
     */
    public function testDownCommand()
    {
        $this->db->expects($this->once())
            ->method('select')
            ->will($this->returnValue([['sql' => file_get_contents(__DIR__ . '/fixtures/migration2.sql')]]));

        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(['M2: DOWN-1'], ['M2: DOWN-2']);

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->db);
        $migrator->setLogger($logger = new TestLogger);

        $migrator->down('migration2.sql');
        $this->assertEquals(['M2: DOWN-1', 'M2: DOWN-2'], $logger->getLog());
    }

}
