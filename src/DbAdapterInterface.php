<?php namespace Usend\Migrations;


interface DbAdapterInterface
{
    /**
     * Escape special chars
     *
     * @param  string $value
     * @return string
     */
    public function escape($value);


    /**
     * Выполнить SQL
     *
     * @param  string $sql
     */
    public function execute($sql);


    /**
     * Вернуть весь результат запроса ввиде ассоциативного массисва
     *
     * @param  string $sql
     * @return array - Массив всех строк, например:
     *  [
     *      [id=>1, name=>Ivan],
     *      [id=>2, name=>Olga],
     *  ]
     */
    public function selectList($sql);
}
