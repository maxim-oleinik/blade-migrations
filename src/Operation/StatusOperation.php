<?php namespace Blade\Migrations\Operation;

use Blade\Migrations\MigrationService;

class StatusOperation
{
    /**
     * The migrator instance.
     *
     * @var MigrationService
     */
    protected $migrator;

    /**
     * Конструктор
     *
     * @param  MigrationService $migrator
     */
    public function __construct(MigrationService $migrator)
    {
        $this->migrator = $migrator;
    }


    /**
     * Run
     */
    public function getData()
    {
        $migrations = $this->migrator->status();

        if (!$migrations) {
            return [];
        }

        $data = [];
        $newMigrations = [];
        foreach ($migrations as $migration) {
            $name = $migration->getName();
            if ($migration->isNew()) {
                $status = '<comment>A</comment>';
                $name = "<comment>{$name}</comment>";
            } else if ($migration->isRemove()) {
                $status = '<fg=red>D</fg=red>';
                $name = "<fg=red>{$name}</fg=red>";
            } else {
                $status = '<info>Y</info>';
            }

            $row = [
                $status,
                $migration->getId(),
                $migration->isNew() ? '' : $migration->getDate()->format('d.m.Y H:i:s'),
                $name
            ];

            if ($migration->isNew()) {
                $newMigrations[] = $row;
            } else {
                $data[] = $row;
            }
        }

        $data = array_reverse($data);

        return array_merge($data, $newMigrations);
    }
}
