<?php namespace Blade\Migrations\Repository;

use Blade\Migrations\Migration;

/**
 * Доступ к миграциям сохраненным в файлах
 */
class FileRepository
{
    /**
     * @var string - Path to Migrations dir
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
    public function all()
    {
        $finder = new \Symfony\Component\Finder\Finder;
        $finder->files()->in($this->dir)->sortByName();
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
        $fileName = $this->_getFileName($migration);
        if (is_file($fileName)) {
            throw new \InvalidArgumentException(__METHOD__.": Migration file `{$migration->getName()}` exists");
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
        $fileName = $this->_getFileName($migration);
        if (!is_file($fileName)) {
            throw new \InvalidArgumentException(__METHOD__.": migration file `{$migration->getName()}` not found in dir `{$this->dir}`");
        }
        $migration->setSql(file_get_contents($fileName));
    }


    /**
     * @param  Migration $migration
     * @return string
     */
    private function _getFileName(Migration $migration): string
    {
        if (!$migration->getName()) {
            throw new \InvalidArgumentException(__METHOD__ . ": Expected Migration has Name");
        }

        $fileName = $this->dir . DIRECTORY_SEPARATOR . $migration->getName();
        return $fileName;
    }
}
