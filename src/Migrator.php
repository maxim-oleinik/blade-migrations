<?php namespace Usend\Migrations;

use Psr\Log\LoggerInterface;


/**
 * @see \Usend\Migrations\Test\MigratorStatusTest
 * @see \Usend\Migrations\Test\MigratorTest
 * @see \Usend\Migrations\Test\MigrateCommandTest
 */
class Migrator implements \Psr\Log\LoggerAwareInterface
{
    /**
     * @var string
     */
    private $migrationsDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MigrationsRepository
     */
    private $repository;


    /**
     * Конструктор
     *
     * @param string               $migrationsDir
     * @param MigrationsRepository $repository
     */
    public function __construct($migrationsDir, MigrationsRepository $repository)
    {
        $this->migrationsDir = $migrationsDir;
        $this->repository = $repository;
    }


    /**
     * @return MigrationsRepository
     */
    public function getRepository()
    {
        return $this->repository;
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
        $migrations = $this->repository->all();
        $found = $this->_get_migrations_list();

        $nameIndex = [];
        foreach ($migrations as $migration) {
            $nameIndex[$migration->getName()] = $migration;
            if (!isset($found[$migration->getName()])) {
                $migration->isRemove(true);
            }
        }

        foreach ($found as $name => $path) {
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
     *
     */
    public function migrate()
    {
        $migrations = $this->status();
        if (empty($migrations['up']) && empty($migrations['down'])) {
            $this->logger->notice('Nothing to migrate');
            return;
        }

        if (!empty($migrations['down'])) {
            foreach ($migrations['down'] as $migrationName) {
                $this->down($migrationName);
            }
        }

        if (!empty($migrations['up'])) {
            foreach ($migrations['up'] as $migrationName) {
                $this->up($migrationName);
            }
        }
    }


    /**
     * @return array
     */
    private function _get_migrations_list()
    {
        $finder = new \Symfony\Component\Finder\Finder;
        $finder->files()->in($this->migrationsDir);

        $result = [];
        foreach ($finder as $file) {
            $result[$file->getBasename()] = $file->getPath();
        }

        return $result;
    }

    /**
     * @param \Usend\Migrations\Migration $migration
     */
    public function up(Migration $migration)
    {
        $this->_make_migration($migration);
        foreach ($migration->getUp() as $sql) {
            $this->logger->info($sql);
            $this->repository->getAdapter()->execute($sql);
        }
    }

    /**
     * @param \Usend\Migrations\Migration $migration
     */
    public function down(Migration $migration)
    {
        $this->repository->loadSql($migration);

        foreach ($migration->getDown() as $sql) {
            $this->logger->info($sql);
            $this->repository->getAdapter()->execute($sql);
        }
    }

    /**
     * @param $migration
     */
    private function _make_migration(Migration $migration)
    {
        // получить миграцию
        $fileName = $this->migrationsDir . DIRECTORY_SEPARATOR . $migration->getName();
        if (!is_file($fileName)) {
            throw new \InvalidArgumentException(__METHOD__.": migration file `{$migration->getName()}` not found in dir `{$this->migrationsDir}`");
        }

        $migration->setSql(file_get_contents($fileName));
    }

}
