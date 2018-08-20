<?php namespace Test\Blade\Database\Acceptance;

use Blade\Database\DbAdapter;
use Blade\Database\DbConnectionInterface;
use Blade\Database\Sql\SqlBuilder;
use Blade\Migrations\Migration;
use Blade\Migrations\MigrationService;
use Blade\Migrations\Operation\MigrateOperation;
use Blade\Migrations\Operation\RollbackOperation;
use Blade\Migrations\Operation\StatusOperation;
use Blade\Migrations\Repository\DbRepository;
use Blade\Migrations\Repository\FileRepository;
use Blade\Migrations\Test\TestLogger;

abstract class BaseAcceptanceScenarioTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param \Blade\Database\DbConnectionInterface $connection
     */
    public function runScenario(DbConnectionInterface $connection)
    {
        $db = new DbAdapter($connection);
        SqlBuilder::setEscapeMethod([$connection, 'escape']);

        $tableName = 'tmp_blade_migrations';

        $logger = new TestLogger();
        $repoFiles = new FileRepository(__DIR__ . '/migrations');
        $repoDb    = new DbRepository($tableName, $db);
        $service = new MigrationService($repoFiles, $repoDb);
        $opStatus = new StatusOperation($service);
        $opMigrate = new MigrateOperation($service);
        $opMigrate->setLogger($logger);
        $opRollback = new RollbackOperation($service);
        $opRollback->setLogger($logger);

        try {
            $baseSql = SqlBuilder::make()->from($tableName);
            $repoDb->install();

            /**
             * Начальный статус
             *
             * A - 01.sql
             * A - 02.sql
             * A - 03.sql
             */
            $data = $opStatus->getData();
            $this->_assertStatus([
                ['A', null, '', "01.sql"],
                ['A', null, '', "02.sql"],
                ['A', null, '', "03.sql"],
            ], $data);


            /**
             * Миграция 2 - Вне очереди
             *
             * Y - 02.sql
             * A - 01.sql
             * A - 03.sql
             */
            $opMigrate->run(null, '02.sql');
            $this->_assertStatus([
                ['Y', 'id', 'date', "02.sql"],
                ['A', null, '', "01.sql"],
                ['A', null, '', "03.sql"],
            ], $opStatus->getData());


            /**
             * Добавим отсутсвующую миграцию
             *
             * Y - 02.sql
             * D - Unknown
             * A - 01.sql
             * A - 03.sql
             */
            $mUnknown = new Migration(null, 'Unknown');
            $mUnknown->setSql("--UP\n--DOWN\n");
            $repoDb->insert($mUnknown);
            $this->_assertStatus([
                ['Y', 'id', 'date', "02.sql"],
                ['D', 'id', 'date', $mUnknown->getName()],
                ['A', null, '', "01.sql"],
                ['A', null, '', "03.sql"],
            ], $opStatus->getData());


            /**
             * Миграция 1
             *
             * Y - 02.sql
             * D - Unknown
             * Y - 01.sql
             * A - 03.sql
             */
            $opMigrate->run();
            $this->_assertStatus([
                ['Y', 'id', 'date', "02.sql"],
                ['D', 'id', 'date', $mUnknown->getName()],
                ['Y', 'id', 'date', "01.sql"],
                ['A', null, '', "03.sql"],
            ], $opStatus->getData());
            $this->assertTrue((bool)$db->selectValue($baseSql->copy()->andWhere('test_col1=11')->exists()));


            /**
             * Миграция 3 - Без транзакции и сепаратор
             *
             * Y - 02.sql
             * D - Unknown
             * Y - 01.sql
             * Y - 03.sql
             */
            $opMigrate->run();
            $this->_assertStatus([
                ['Y', 'id', 'date', "02.sql"],
                ['D', 'id', 'date', $mUnknown->getName()],
                ['Y', 'id', 'date', "01.sql"],
                ['Y', 'id', 'date', "03.sql"],
            ], $opStatus->getData());
            $row = $db->selectRow($baseSql->copy()->limit(1));
            $this->assertEquals(22, $row['test_col2'], 'Была добавлена колонка с дефолтом 22');
            $this->assertFalse(isset($row['test_col1']), 'Колонка 1 была удалена');


            /**
             * Откат последней - Миграция 3
             *
             * Y - 02.sql
             * D - Unknown
             * Y - 01.sql
             * A - 03.sql
             */
            $opRollback->run();
            $this->_assertStatus([
                ['Y', 'id', 'date', "02.sql"],
                ['D', 'id', 'date', $mUnknown->getName()],
                ['Y', 'id', 'date', "01.sql"],
                ['A', null, '', "03.sql"],
            ], $opStatus->getData());
            $row = $db->selectRow($baseSql->copy()->limit(1));
            $this->assertEquals(11, $row['test_col1'], 'Была добавлена колонка с дефолтом 11');
            $this->assertFalse(isset($row['test_col2']), 'Колонка 2 была удалена');


            /**
             * Auto - откат Unknown и добавить Миграцию 3
             *
             * Y - 02.sql
             * Y - 01.sql
             * Y - 03.sql
             */
            $opMigrate->setAuto(true);
            $opMigrate->run();
            $this->_assertStatus([
                ['Y', 'id', 'date', "02.sql"],
                ['Y', 'id', 'date', "01.sql"],
                ['Y', 'id', 'date', "03.sql"],
            ], $opStatus->getData());
            $row = $db->selectRow($baseSql->copy()->limit(1));
            $this->assertEquals(22, $row['test_col2'], 'Была добавлена колонка с дефолтом 22');
            $this->assertFalse(isset($row['test_col1']), 'Колонка 1 была удалена');


            /**
             * DbRepository
             *
             * Y - 02.sql
             * Y - 01.sql
             * Y - 03.sql
             */
            $items = $repoDb->all();
            $this->assertEquals('03.sql', $items[0]->getName());
            $this->assertEquals('01.sql', $items[1]->getName());
            $this->assertEquals('02.sql', $items[2]->getName());

            $m = $repoDb->findLast();
            $this->assertEquals('03.sql', $m->getName());

            $m = $repoDb->findById($items[1]->getId());
            $this->assertEquals($items[1]->getName(), $m->getName());

        } catch (\Exception $e) {
        }

        $db->execute("DROP TABLE IF EXISTS {$tableName}");

        if (isset($e)) {
            throw $e;
        }
    }


    private function _assertStatus(array $expected, array $actual, $message = null)
    {
        foreach ($expected as &$row) {
            $row = $this->_prepareStatusRow($row);
        }
        foreach ($actual as &$row) {
            $row = $this->_prepareStatusRow($row);
        }

        $this->assertEquals($expected, $actual, $message);

    }

    private function _prepareStatusRow(array $row)
    {
        $row[0] = strip_tags($row[0]);
        if (!empty($row[1])) {
            $row[1] = 'ID';
        }
        if (!empty($row[2])) {
            $row[2] = 'DATE';
        }
        $row[3] = strip_tags($row[3]);
        return $row;
    }
}
