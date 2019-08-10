<?php namespace Blade\Migrations\Operation;

use Blade\Migrations\MigrationService;

/**
 * Rollback Migration
 *
 * Config
 *   - setForce(bool) - Не спрашивать подтверждение
 *   - run(callable $confirmationCallback, $migrationId, $loadFromFile) - см. описание метода
 *
 * @see \Test\Blade\Migrations\Operation\RollbackOperationTest
 */
class RollbackOperation extends BaseOperation
{
    /**
     * @var MigrationService
     */
    private $service;

    /**
     * @var bool
     */
    private $optForce = false;

    /**
     * Constructor
     *
     * @param  MigrationService $service
     */
    public function __construct(MigrationService $service)
    {
        $this->service = $service;
    }

    /**
     * @param bool $force
     */
    public function setForce($force)
    {
        $this->optForce = (bool)$force;
    }


    /**
     * Run
     *
     * @param callable|null $confirmationCallback - Спросить подтверждение у пользователя перед запуском каждой миграции
     *                                            функция должна вернуть true/false
     *                                            принимает $migrationTitle
     * @param int           $migrationId          - ID конкретной миграции, которую надо накатить
     * @param bool          $loadFromFile         - Загрузить текст миграции из файла вместо БД
     *
     * @return null|\Blade\Migrations\Migration - Если успешно, возвращает Миграцию
     */
    public function run(callable $confirmationCallback = null, $migrationId = null, $loadFromFile = false)
    {
        if ($this->logger) {
            $this->service->setLogger($this->logger);
        }

        // Выбрать миграцию
        if ($migrationId) {
            if ($next = $this->service->getDbRepository()->findById($migrationId)) {
            } else {
                $this->error("<error>Migration with ID={$migrationId} not found!</error>");
                return;
            }
        } else {
            $next = $this->service->getDbRepository()->findLast();
        }

        if (empty($next)) {
            $this->alert('<error>Nothing to rollback</error>');
            return;
        }

        $title = 'Rollback: ' . $next->getName();
        if ($this->optForce) {
            $this->error("<error>{$title}</error>");

        // Запросить подтверждение
        } elseif ($confirmationCallback && !$confirmationCallback($title)) {
            return;
        }

        $this->service->down($next, $loadFromFile);
        $this->info('Done');
        return $next;
    }
}
