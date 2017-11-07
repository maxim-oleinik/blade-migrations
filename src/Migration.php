<?php

class Migration
{
    const TAG_UP = '--UP';
    const TAG_DOWN = '--DOWN';

    private $up = [];
    private $down = [];


    /**
     * Конструктор
     *
     * @param string $sql
     */
    public function __construct($sql)
    {
        $sql = trim($sql);
        if (strpos($sql, self::TAG_UP) !== 0) {
            throw new \InvalidArgumentException(__METHOD__. ": UP tag not found");
        }
        $sql = trim(substr($sql, strlen(self::TAG_UP)));

        if (strpos($sql, self::TAG_DOWN) === false) {
            throw new \InvalidArgumentException(__METHOD__. ": DOWN tag not found");
        }

        list($up, $down) = explode(self::TAG_DOWN, $sql);
        $this->up = array_filter(array_map('trim', explode(';', trim($up))));
        $this->down = array_filter(array_map('trim', explode(';', trim($down))));
    }

    /**
     * @return array
     */
    public function getUp()
    {
        return $this->up;
    }

    /**
     * @return array
     */
    public function getDown()
    {
        return $this->down;
    }


}
