<?php namespace Blade\Migrations\Operation;

use Blade\Migrations\MigrationService;

/**
 * Выполнить миграцию
 *
 * Конфигурация
 *   - setAuto(bool)  - Выполнить ВЕСЬ набор доступных Миграций (включая Откаты)
 *   - setForce(bool) - Не спрашивать подтверждение
 *   - run(callable $confirmationCallback = null) - см. описание метода
 *
 * @see \Test\Blade\Migrations\Operation\MigrateOperationTest
 */
class MigrateOperation extends BaseOperation
{
    /**
     * @var MigrationService
     */
    private $service;

    /**
     * @var bool
     */
    private $optAuto = false;

    /**
     * @var bool
     */
    private $optForce = false;

    /**
     * @var bool
     */
    private $optTestRollback = false;

    /**
     * Конструктор
     *
     * @param  MigrationService $service
     */
    public function __construct(MigrationService $service)
    {
        $this->service = $service;
    }

    /**
     * @param bool $flag
     */
    public function setAuto(bool $flag)
    {
        $this->optAuto = $flag;
    }

    /**
     * @param bool $flag
     */
    public function setForce(bool $flag)
    {
        $this->optForce = $flag;
    }

    /**
     * @param bool $flag
     */
    public function setTestRollback(bool $flag)
    {
        $this->optTestRollback = $flag;
    }


    /**
     * Run
     *
     * @param callable|null $confirmationCallback - Спросить подтверждение у пользователя перед запуском каждой миграции
     *                                            функция должна вернуть true/false
     *                                            принимает $migrationTitle
     * @param string        $migrationName        - Название миграции которую надо явно запустить
     */
    public function run(callable $confirmationCallback = null, $migrationName = null)
    {
        if ($this->logger) {
            $this->service->setLogger($this->logger);
        }

        // Выбранную миграцию
        if ($migrationName) {
            if (strpos($migrationName, DIRECTORY_SEPARATOR) !== false) {
                $migrationName = basename($migrationName);
            }
            $migrations = [];
            foreach ($this->service->getDiff(true) as $migration) {
                if ($migration->getName() === $migrationName) {
                    $migrations[] = $migration;
                }
            }
            if (!$migrations) {
                $this->alert("<error>Migration `{$migrationName}` not found or applied already</error>");
                return;
            }

        // Все миграции по списку
        } else {
            $migrations = $this->service->getDiff(!$this->optAuto); // только новые
        }


        if (!$migrations) {
            $this->alert('<error>Nothing to migrate</error>');
            return;
        }

        if ($this->optAuto && $this->optTestRollback) {
            $this->alert('<error>Ignore "Test Rollback" option with "Auto" option</error>');
        }

        foreach ($migrations as $next) {

            $title = $next->getName();
            if ($next->isRemove()) {
                $title = 'Rollback: ' . $title;
            }

            // Показать заголовок Мигации
            if ($this->optForce || !$confirmationCallback) {
                // Добавление
                if ($next->isNew()) {
                    $this->info("<info>{$title}</info>");
                    if ($this->_isTestRollback()) {
                        $this->info('<error>WITH Rollback</error>');
                    }
                // Удаление
                } else {
                    $this->info("<error>{$title}</error>");
                }

            // Если без --force, то спрашиваем подтверждение на каждую миграцию
            } elseif (!$confirmationCallback($title)) {
                return;
            }

            if ($next->isNew() && !$next->isRemove()) {
                $this->service->up($next, $this->_isTestRollback());

            } elseif (!$next->isNew() && $next->isRemove()) {
                $this->service->down($next);
            }

            if (!$this->optAuto) {
                break;
            }
        }

        $this->info('Done');
    }

    /**
     * @return bool
     */
    private function _isTestRollback(): bool
    {
        return $this->optTestRollback && !$this->optAuto;
    }
}
