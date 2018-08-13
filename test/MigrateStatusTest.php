<?php namespace Blade\Migrations\Test;

use Blade\Migrations\Migration;
use Blade\Migrations\Repository\DbRepository;
use Blade\Migrations\MigrationService;
use Blade\Migrations\Repository\FileRepository;

/**
 * @see \Blade\Migrations\MigrationService
 */
class MigrateStatusTest extends \PHPUnit_Framework_TestCase
{
    private $repository;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->repository = $this->createMock(DbRepository::class);
    }


    /**
     * Статус - нет файлов миграций
     */
    public function testStatusNoMigrations()
    {
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures/empty_dir'), $this->repository);
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
        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);

        $this->repository->expects($this->any())
            ->method('items')
            ->will($this->returnValue([]));

        $result = $migrator->status();
        $this->assertEquals([
            $m3 = new Migration(null, 'migration-no-trans.sql', null),
            $m1 = new Migration(null, 'migration1.sql', null),
            $m2 = new Migration(null, 'migration2.sql', null),
        ], $result);

        $this->assertEquals([$m3, $m1, $m2], $migrator->getDiff());
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

        $migrator = new MigrationService(new FileRepository(__DIR__ . '/fixtures'), $this->repository);
        $result = $migrator->status();
        // Накатить М2 и откатить М3
        $this->assertEquals([
            $m11, // уже применена
            $m33, // удалить
            $m4 = new Migration(null, 'migration-no-trans.sql', null), // новая
            $m2 = new Migration(null, 'migration2.sql', null), // новая
        ], $result);

        $this->assertEquals([$m3, $m4, $m2], $migrator->getDiff());
        $this->assertEquals([$m4, $m2], $migrator->getDiff(true), 'Показать только Новые миграции');
    }

}
