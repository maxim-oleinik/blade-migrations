<?php namespace Usend\Migrations\Repository;

use Usend\Migrations\Migration;


class FileRepository
{
    /**
     * @var string - DB table name
     */
    private $dir;


    /**
     * Конструктор
     *
     * @param string $migrationsDir
     */
    public function __construct($migrationsDir)
    {
        $this->dir = $migrationsDir;
    }


    /**
     * Получить список всех Миграций
     *
     * @return array - FILE => PATH
     */
    public function items()
    {
        $finder = new \Symfony\Component\Finder\Finder;
        $finder->files()->in($this->dir);
        $found = [];
        foreach ($finder as $file) {
            $found[$file->getBasename()] = $file->getPath();
        }

        return $found;
    }


    /**
     * Добавить Миграцию в файл
     *
     * @param Migration $migration
     */
    public function insert(Migration $migration)
    {
        $fileName = $this->dir . DIRECTORY_SEPARATOR . $migration->getName();
        if (is_file($fileName)) {
            throw new \InvalidArgumentException(__METHOD__.": migration file `{$migration->getName()}` exists");
        }

        file_put_contents($fileName, $migration->getSql());
    }


    /**
     * Загрузить SQL
     *
     * @param Migration $migration
     */
    public function loadSql(Migration $migration)
    {
        $fileName = $this->dir . DIRECTORY_SEPARATOR . $migration->getName();
        if (!is_file($fileName)) {
            throw new \InvalidArgumentException(__METHOD__.": migration file `{$migration->getName()}` not found in dir `{$this->dir}`");
        }
        $migration->setSql(file_get_contents($fileName));
    }

}
