<?php namespace Usend\Migrations\Test;

use Usend\Migrations\Migration;
use Usend\Migrations\MigrationsRepository;
use Usend\Migrations\Migrator;

/**
 * @see \Usend\Migrations\Migrator
 */
class MigrateStatusTest extends \PHPUnit_Framework_TestCase
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
     * Статус - нет файлов миграций
     */
    public function testStatusNoMigrations()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures/empty_dir', $this->repository);
        $this->repository->expects($this->any())
            ->method('items')
            ->will($this->returnValue([]));

        $result = $migrator->status();
        $this->assertSame([], $result, 'Нет ничего');
        $this->assertSame([], $migrator->getDiff());
    }


    /**
     * Статус - миграции UP
     */
    public function testStatusUp()
    {
        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);

        $this->repository->expects($this->any())
            ->method('items')
            ->will($this->returnValue([]));

        $result = $migrator->status();
        $this->assertEquals([
            $m1 = new Migration(null, 'migration1.sql', null),
            $m2 = new Migration(null, 'migration2.sql', null),
        ], $result);

        $this->assertEquals([$m1, $m2], $migrator->getDiff());
    }


    /**
     * Статус - все миграции - Up, Down, Applied
     */
    public function testStatusAll()
    {
        // В базе зафиксированы 2 миграции
        $this->repository->expects($this->any())
            ->method('items')
            ->will($this->returnValue([
                $m1 = new Migration(1, 'migration1.sql', null),
                $m3 = new Migration(3, 'migration3.sql', null),
            ]));
        $m11 = clone $m1;
        $m33 = clone $m3;
        $m33->isRemove(true);

        $migrator = new Migrator(__DIR__ . '/fixtures', $this->repository);
        $result = $migrator->status();
        // Накатить М2 и откатить М3
        $this->assertEquals([
            $m11, // уже применена
            $m33, // удалить
            $m2 = new Migration(null, 'migration2.sql', null), // новая
        ], $result);

        $this->assertEquals([$m3, $m2], $migrator->getDiff());
    }

}
