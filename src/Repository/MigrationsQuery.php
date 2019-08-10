<?php namespace Blade\Migrations\Repository;

use Blade\Database\Sql\SqlBuilder;

class MigrationsQuery extends SqlBuilder
{
    /**
     * Filter By ID
     *
     * @param  int $id
     * @return self
     */
    public function filterById(int $id): self
    {
        return $this->andWhereEquals($this->col('id'), $id);
    }


    /**
     * Filter By Name
     *
     * @param  string $name
     * @return self
     */
    public function filterByName(string $name): self
    {
        return $this->andWhereEquals($this->col('name'), $name);
    }
}
