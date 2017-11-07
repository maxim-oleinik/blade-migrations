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

}
