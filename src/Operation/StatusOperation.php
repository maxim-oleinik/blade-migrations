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
     * Constructor
     *
     * @param  MigrationService $migrator
     */
    public function __construct(MigrationService $migrator)
    {
        $this->migrator = $migrator;
    }


    /**
     * Run
     *
     * @return array
     */
    public function getData(): array
    {
        $migrations = $this->migrator->status();

        if (!$migrations) {
            return [];
        }

        $data = [];
        $newMigrations = [];
        foreach ($migrations as $migration) {
            if ($migration->isNew()) {
                $tpl = '<info>%s</info>';
                $status = 'A';
            } elseif ($migration->isRemove()) {
                $tpl = '<fg=red>%s</fg=red>';
                $status = 'D';
            } else {
                $tpl = '%s';
                $status = '<comment>Y</comment>';
            }

            $row = [
                sprintf($tpl, $status),
                $migration->getId() ? sprintf($tpl, $migration->getId())  : '',
                $migration->isNew() ? '' : sprintf($tpl, $migration->getDate()->format('d.m.Y H:i:s')),
                sprintf($tpl, $migration->getName()),
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
