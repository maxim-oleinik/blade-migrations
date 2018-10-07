Blade/Migrations
================
[![Latest Stable Version](https://poser.pugx.org/maxim-oleinik/blade-migrations/v/stable)](https://packagist.org/packages/maxim-oleinik/blade-migrations)

Библиотека предоставляет API для реализации консольных команд для управления миграциями БД.
* Laravel/Artisan - https://github.com/maxim-oleinik/blade-migrations-laravel

**Особенности:**
* миграции на чистом SQL (на любом нативном языке БД)
* нет своего конфига и коннекта к БД - использует существующее в проекте соединение. Можно использовать для миграций любых баз.
* автоматический откат миграций созданных в других ветках


Файл миграции
-------------
**Пример файла миграций**
* `--TRANSACTION` - миграция должна быть запущена в транзации
* Инструкции разделяются тегами `--UP` и `--DOWN`
* SQL запросы разделяются `";"`
```
--TRANSACTION
--UP
ALTER TABLE authors ADD COLUMN code INT;
ALTER TABLE posts   ADD COLUMN slug TEXT;

--DOWN
ALTER TABLE authors DROP COLUMN code;
ALTER TABLE posts   DROP COLUMN slug;
```

**Если надо сменить раделитель**, когда в SQL необходимо использовать `";"`
```
--TRANSACTION
--SEPARATOR=@
--UP
    ... sql@
    ... sql@

--DOWN
    ... sql@
    ... sql@
```


Установка и настройка
---------

1. Добавить в **composer**
    ```
        composer require maxim-oleinik/blade-migrations
    ```

2. Подключить к своей БД - необходимо реализовать интерфейс `\Blade\Database\DbConnectionInterface`  
    см. https://github.com/maxim-oleinik/blade-database

3. Сборка
    ```
        $conn      = new MyDbConnection; // implements \Blade\Database\DbConnectionInterface
        $dbAdapter = new \Blade\Database\DbAdapter($conn);
        $repoDb    = new \Blade\Migrations\Repository\DbRepository($migrationTableName = 'migrations', $dbAdapter);
        $repoFile  = new \Blade\Migrations\Repository\FileRepository($migrationsDir = __DIR__ . '/migrations');
        $service   = new \Blade\Migrations\MigrationService($repoFile, $repoDb);
    ```



Команды
-------

### Status - `\Blade\Migrations\Operation\StatusOperation`
```
    $op = new \Blade\Migrations\Operation\StatusOperation($service);
    $data = $op->getData();
```

Возвращает массив массив данных по текущему состоянию миграций
```
 [
      [
          CODE,
          ID,
          DATE,
          NAME,
      ],
 ]
```
Где CODE:
*   Y - выполнена
*   D - требует отката (в текущей ветке ее нет)
*   A - в очереди


### Migrate - `\Blade\Migrations\Operation\MigrateOperation`
```
    $op = new \Blade\Migrations\Operation\MigrateOperation($service);

    // Передать логгер в миграцию для вывода данных
    $op->setLogger(\Psr\Log\LoggerInterface $logger);
    
    $op->setAuto(bool); // Автооткат отсутствющих в текущей ветке миграций (есть в базе, но нет на диске)
    $op->setForce(bool); // Спрашивать подтверждение

    /**
     * @param $confirmationCallback - функция, которая спросит подтверждение у пользователя и вернет true/false
     * @param $migrationName - Название миграции. Если указано, принудительно запустит ее вне очереди
     */
    $op->run(callable $confirmationCallback($migrationTitle), $migrationName = null);
```


### Rollback - `\Blade\Migrations\Operation\RollbackOperation`
```
    $op = new \Blade\Migrations\Operation\RollbackOperation($service);

    // Передать логгер в миграцию для вывода данных
    $op->setLogger(\Psr\Log\LoggerInterface $logger);

    $op->setForce(bool); // Спрашивать подтверждение

    /**
     * @param $confirmationCallback - функция, которая спросит подтверждение у пользователя и вернет true/false
     * @param $migrationId - ID миграции в базе. Если указано, принудительно откатит ее вне очереди
     * @param $loadFromFile - Загрузить миграцию из файла, а не из БД (например, если в базу попала ошибка)
     */
    $op->run(callable $confirmationCallback($migrationTitle), $migrationId = null, $loadFromFile = false);
```

### Создать миграцию - `\Blade\Migrations\Operation\MakeOperation`
```
    $op = new \Blade\Migrations\Operation\MakeOperation($repoFile)
    $op->run($migrationFileName);
```

### Install - `\Blade\Migrations\Repository\DbRepository`
Создать таблицу миграций в Базе
```
    $repoDb->install();
```
