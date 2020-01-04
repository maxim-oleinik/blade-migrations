Blade/Migrations
================

[rus](./README.rus.md) /
[![Latest Stable Version](https://poser.pugx.org/maxim-oleinik/blade-migrations/v/stable)](https://packagist.org/packages/maxim-oleinik/blade-migrations)
<a href="https://packagist.org/packages/maxim-oleinik/blade-migrations"><img src="https://poser.pugx.org/maxim-oleinik/blade-migrations/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/maxim-oleinik/blade-migrations"><img src="https://poser.pugx.org/maxim-oleinik/blade-migrations/license.svg" alt="License"></a>

This library provides Database structure manipulation commands, see implementations:
* Symfony/Console - https://github.com/maxim-oleinik/blade-migrations-symfony
* Laravel/Artisan - https://github.com/maxim-oleinik/blade-migrations-laravel


Features
-----------
* **Using raw SQL queries**
    * you can use all the capabilities of your database to describe the structure and changes
    * easy work with procedures and functions
    * safe data migrations (INSERT/UPDATE)
    * IDE native syntax support
* **Running migrations within a transaction** with automatic rollback in case of an error (if your database supports it, PostgreSQL for example)
* **Dynamic output running the SQL-queries**
* **Automatic rollback** after switching the branch (for reviewing, testing, demo, building at permanent/staging database)
* **Auto-update the migration after editing** (version change in the name of the migration file)
* **Apply with rollback testing** - `UD-DOWN-UP`
* **Rollback or Reload any selected migration**


Syntax
---------
* `--TRANSACTION` - if specified, the migration will be launched within a transaction
* Instructions are separated by `--UP` and` --DOWN` tags.
* The SQL queries are separated by `";"` (the last character at the end of the line)

```
--TRANSACTION
--UP
ALTER TABLE authors ADD COLUMN code INT;
ALTER TABLE posts   ADD COLUMN slug TEXT;

--DOWN
ALTER TABLE authors DROP COLUMN code;
ALTER TABLE posts   DROP COLUMN slug;
```

**If you need to change the delimiter** (when in SQL you have to use `";"`)
```
--SEPARATOR=@
--UP
    ... some sql ...@
    ... some sql ...@

--DOWN
    ... some sql ...@
    ... some sql ...@
```


Install
---------

1. Composer
    ```
        composer require maxim-oleinik/blade-migrations
    ```

2. Implement `\Blade\Database\DbConnectionInterface` to connect with your database,
   see https://github.com/maxim-oleinik/blade-database

3. Setting up
    ```
        $conn      = new MyDbConnection; // implements \Blade\Database\DbConnectionInterface
        $dbAdapter = new \Blade\Database\DbAdapter($conn);
        $repoDb    = new \Blade\Migrations\Repository\DbRepository($migrationTableName = 'migrations', $dbAdapter);
        $repoFile  = new \Blade\Migrations\Repository\FileRepository($migrationsDir = __DIR__ . '/migrations');
        $service   = new \Blade\Migrations\MigrationService($repoFile, $repoDb);
    ```


Commands
-------

### Status - `\Blade\Migrations\Operation\StatusOperation`
```
    $op = new \Blade\Migrations\Operation\StatusOperation($service);
    $data = $op->getData();
```

Returns an array with current migrations state
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
Where CODE is:
* **Y** - applied migration
* **D** - have to rollback (no this migration in the current branch/revision)
* **A** - not applied yet, next to be run


### Migrate - `\Blade\Migrations\Operation\MigrateOperation`
```
    $op = new \Blade\Migrations\Operation\MigrateOperation($service);

    // Set logger for output
    $op->setLogger(\Psr\Log\LoggerInterface $logger);

    $op->setAuto(bool); // Auto-migrate all - rollback all D-migrations and appply all А-migrations
    $op->setForce(bool); // Apply the migration without a prompt
    $op->setTestRollback(bool); // rollback testing: UP-DOWN-UP

    /**
     * @param $confirmationCallback - Function, which asks for a prompt and returns true/false
     * @param $migrationName - Concrete migration name
     */
    $op->run(callable $confirmationCallback($migrationTitle), $migrationName = null);
```


### Rollback - `\Blade\Migrations\Operation\RollbackOperation`
```
    $op = new \Blade\Migrations\Operation\RollbackOperation($service);

    // Set logger for output
    $op->setLogger(\Psr\Log\LoggerInterface $logger);

    $op->setForce(bool);

    /**
     * @param $confirmationCallback - Function, which asks for a prompt and returns true/false
     * @param $migrationId - Database migration ID
     * @param $loadFromFile - Rollback migration with commands taken from migration file, not from DB (if saved version contains error)
     */
    $op->run(callable $confirmationCallback($migrationTitle), $migrationId = null, $loadFromFile = false);
```

### Make migration file - `\Blade\Migrations\Operation\MakeOperation`
```
    $op = new \Blade\Migrations\Operation\MakeOperation($repoFile)
    $op->run($migrationFileName);
```

### Install - `\Blade\Migrations\Repository\DbRepository`
Create migration table
```
    $repoDb->install();
```
