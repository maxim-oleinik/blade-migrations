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

}