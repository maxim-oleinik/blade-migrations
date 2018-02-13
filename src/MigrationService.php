<?php namespace Usend\Migrations;

use Psr\Log\LoggerInterface;
use Usend\Migrations\Repository\DbRepository;
use Usend\Migrations\Repository\FileRepository;


/**
 * @see \Usend\Migrations\Test\MigrateStatusTest
 * @see \Usend\Migrations\Test\MigrateUpDownTest
 */
class MigrationService implements \Psr\Log\LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * @var DbRepository
     */
    private $dbRepository;


    /**
     * Конструктор
     *
     * @param FileRepository $fileRepository
     * @param DbRepository   $dbRepository
     */
    public function __construct(FileRepository $fileRepository, DbRepository $dbRepository)
    {
        $this->fileRepository = $fileRepository;
        $this->dbRepository = $dbRepository;
    }


    /**
     * @return DbRepository
     */
    public function getDbRepository()
    {
        return $this->dbRepository;
    }


    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @return Migration[]
     */
    public function status()
    {
        // Найти все файлы миграций
        $files = $this->fileRepository->items();

        $migrations = $this->dbRepository->items();
        $nameIndex = [];
        foreach ($migrations as $migration) {
            $nameIndex[$migration->getName()] = $migration;
            if (!isset($files[$migration->getName()])) {
                $migration->isRemove(true);
            }
        }

        foreach ($files as $name => $path) {
            if (!isset($nameIndex[$name])) {
                $migrations[] = new Migration(null, $name, null);
            }
        }
        return $migrations;
    }


    /**
     * @return Migration[]
     */
    public function getDiff()
    {
        $up = [];
        $down = [];
        foreach ($this->status() as $migration) {
            if ($migration->isNew()) {
                $up[] = $migration;
            } else if ($migration->isRemove()) {
                $down[] = $migration;
            }
        }

        return array_merge($down, $up);
    }


    /**
     * UP
     *
     * @param Migration $migration
     * @throws \Exception
     */
    public function up(Migration $migration)
    {
        if (!$migration->isNew()) {
            throw new \InvalidArgumentException(__METHOD__.": Expected NEW migration");
        }

        // Загрузить SQL
        $this->fileRepository->loadSql($migration);

        $func = function () use ($migration) {
            $this->_processMigrationSql($migration->getUp());
            $this->getDbRepository()->insert($migration);
        };

        if ($migration->isTransaction()) {
            $this->getDbRepository()->getAdapter()->transaction($func);
        } else {
            $func();
        }
    }


    /**
     * DOWN
     *
     * @param \Usend\Migrations\Migration $migration
     * @param bool                        $loadFromFile
     */
    public function down(Migration $migration, $loadFromFile = false)
    {
        if ($loadFromFile) {
            $this->fileRepository->loadSql($migration);
        } else {
            $this->dbRepository->loadSql($migration);
        }

        $func = function () use ($migration) {
            $this->_processMigrationSql($migration->getDown());
            $this->dbRepository->delete($migration);
        };

        if ($migration->isTransaction()) {
            $this->getDbRepository()->getAdapter()->transaction($func);
        } else {
            $func();
        }
    }


    /**
     * Выполнить полученный SQL
     *
     * @param array $sqlList
     */
    private function _processMigrationSql(array $sqlList)
    {
        foreach ($sqlList as $sql) {
            if ($this->logger) {
                $this->logger->info($sql.PHP_EOL);
            }
            $this->getDbRepository()->getAdapter()->execute($sql);
        }
    }
}
