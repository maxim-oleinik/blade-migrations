<?php namespace Usend\Migrations;

use Psr\Log\LoggerInterface;


/**
 * @see \Usend\Migrations\Test\MigrateStatusTest
 * @see \Usend\Migrations\Test\MigrateUpDownTest
 */
class MigrationService implements \Psr\Log\LoggerAwareInterface
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
        // Найти все файлы миграций
        $finder = new \Symfony\Component\Finder\Finder;
        $finder->files()->in($this->migrationsDir);
        $found = [];
        foreach ($finder as $file) {
            $found[$file->getBasename()] = $file->getPath();
        }

        $migrations = $this->repository->items();
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
        $fileName = $this->migrationsDir . DIRECTORY_SEPARATOR . $migration->getName();
        if (!is_file($fileName)) {
            throw new \InvalidArgumentException(__METHOD__.": migration file `{$migration->getName()}` not found in dir `{$this->migrationsDir}`");
        }
        $migration->setSql(file_get_contents($fileName));

        $this->getRepository()->getAdapter()->transaction(function () use ($migration) {
            foreach ($migration->getUp() as $sql) {
                if ($this->logger) {
                    $this->logger->info($sql.PHP_EOL);
                }
                $this->getRepository()->getAdapter()->execute($sql);
            }
            $this->getRepository()->insert($migration);
        });
    }


    /**
     * @param \Usend\Migrations\Migration $migration
     */
    public function down(Migration $migration)
    {
        $this->repository->loadSql($migration);

        $this->getRepository()->getAdapter()->transaction(function () use ($migration) {
            foreach ($migration->getDown() as $sql) {
                if ($this->logger) {
                    $this->logger->info($sql.PHP_EOL);
                }
                $this->repository->getAdapter()->execute($sql);
            }
            $this->repository->delete($migration);
        });
    }

}
