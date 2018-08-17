<?php namespace Blade\Migrations\Operation;

use Blade\Migrations\Migration;
use Blade\Migrations\Repository\FileRepository;

class MakeOperation
{
    /**
     * @var FileRepository
     */
    protected $repository;

    /**
     * Конструктор
     *
     * @param FileRepository $repository
     */
    public function __construct(FileRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Run
     *
     * @param  string $name
     * @return string - Название файла Миграции
     */
    public function run($name)
    {
        $fileName = sprintf('%s_%s.sql',
            date('Ymd_His'),
            trim($name)
        );
        $migration = new Migration(null, $fileName);
        $migration->setSql(Migration::TAG_TRANSACTION . PHP_EOL
            . Migration::TAG_UP . PHP_EOL
            . PHP_EOL
            . Migration::TAG_DOWN . PHP_EOL
            . PHP_EOL
        );
        $this->repository->insert($migration);

        return $fileName;
    }
}
