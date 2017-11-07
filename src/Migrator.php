<?php

use Psr\Log\LoggerInterface;

class Migrator implements \Psr\Log\LoggerAwareInterface
{
    private $migrationsDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DbAdapterInterface
     */
    private $db;

    public function __construct($migrationsDir, DbAdapterInterface $db)
    {
        $this->migrationsDir = $migrationsDir;
        $this->db = $db;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function status()
    {
        $result = [
            'up'=>[],
            'down'=>[],
            'current'=>[],
        ];

        $applied = [];
        $appliedResult = $this->db->select("SELECT name FROM _migrations ORDER BY id");
        if ($appliedResult) {
            foreach ($appliedResult as $row) {
                $applied[$row['name']] = true;
            }
        }

        $found = $this->_get_migrations_list();

        foreach ($applied as $name => $f) {
            if (!isset($found[$name])) {
                $result['down'][] = $name;
            } else {
                $result['current'][] = $name;
            }
        }

        foreach ($found as $name => $path) {
            if (!$result['current'] || !isset($applied[$name])) {
                $result['up'][] = $name;
            }
        }
        return $result;
    }

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

    private function _get_migrations_list()
    {
        $finder = new Symfony\Component\Finder\Finder;
        $finder->files()->in($this->migrationsDir);

        $result = [];
        foreach ($finder as $file) {
            $result[$file->getBasename()] = $file->getPath();
        }

        return $result;
    }

    public function up($migrationName)
    {
        $migration = $this->_make_migration($migrationName);
        foreach ($migration->getUp() as $sql) {
            $this->logger->info($sql);
            $this->db->execute($sql);
        }
    }

    public function down($migrationName)
    {
        $data = $this->db->select(sprintf("SELECT sql FROM _migrations WHERE name='%s' LIMIT 1", $this->db->escape($migrationName)));
        if (!$data) {
            throw new InvalidArgumentException(__METHOD__.": migration `{$migrationName}` not found DOWN data in Database");
        }

        $migration = new Migration(current(current($data)));
        foreach ($migration->getDown() as $sql) {
            $this->logger->info($sql);
            $this->db->execute($sql);
        }
    }

    /**
     * @param $migrationName
     * @return \Migration
     */
    private function _make_migration($migrationName)
    {
        // получить миграцию
        $fileName = $this->migrationsDir . DIRECTORY_SEPARATOR . $migrationName;
        if (!is_file($fileName)) {
            throw new InvalidArgumentException(__METHOD__.": migration file `{$migrationName}` not found in dir `{$this->migrationsDir}`");
        }

        return new Migration(file_get_contents($fileName));
    }
}
