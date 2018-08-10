<?php namespace Blade\Migrations;

/**
 * @see \Blade\Migrations\Test\MigrationTest
 */
class Migration
{
    const TAG_UP          = '--UP';
    const TAG_DOWN        = '--DOWN';
    const TAG_TRANSACTION = '--TRANSACTION';
    const TAG_SEPARATOR   = '--SEPARATOR';

    private $up   = [];
    private $down = [];
    private $isRemove = false;
    private $isTransaction = false;
    private $separator = ';';

    private $id;
    private $name;
    private $date;
    private $sql;


    /**
     * Конструктор
     *
     * @param int    $id
     * @param string $name
     * @param string $date
     */
    public function __construct($id, $name, $date = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->date = new \DateTime($date);
    }


    /**
     * @param string $sql
     */
    public function setSql($sql)
    {
        $sql = trim($sql);
        $this->sql = $sql;

        preg_match_all('/(--[A-Z]+)(=.*)?(?:[\s]|$)?/', $sql, $matches, PREG_OFFSET_CAPTURE);

        $up   = null;
        $down = null;

        $posUp   = null;
        $posDown = null;

        foreach ($matches[1] as $i => $data) {
            list($tag, $position) = $data;
            $tag = trim($tag);

            switch ($tag) {
                case self::TAG_UP:
                    if (null !== $posUp) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected single {$tag} tag");
                    }
                    $posUp = $position + strlen($tag);
                    break;

                case self::TAG_DOWN:
                    if (null === $posUp) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected UP tag first");
                    } elseif (null !== $posDown) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected single {$tag} tag");
                    }
                    $posDown = $position + strlen($tag);
                    $up   = trim(substr($sql, $posUp, $position - $posUp));
                    $down = trim(substr($sql, $posDown));
                    break;

                case self::TAG_TRANSACTION:
                    if (null !== $posUp) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected TRANSACTION tag before UP tag");
                    }
                    $this->isTransaction = true;
                    break;

                case self::TAG_SEPARATOR:
                    if (null !== $posUp) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected SEPARATOR tag before UP tag");
                    }
                    $separator = null;
                    if (isset($matches[2][$i][0])) {
                        $separator = substr(trim($matches[2][$i][0]), 1);
                    }
                    if (!$separator) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected SEPARATOR tag has value");
                    } elseif (strlen($separator) > 1) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected SEPARATOR tag has SINGLE CHAR value");
                    }
                    $this->separator = $separator;
                    break;
            }
        }

        if (null === $posUp) {
            throw new \InvalidArgumentException(__METHOD__. ": UP tag not found");
        }
        if (null === $posDown) {
            throw new \InvalidArgumentException(__METHOD__. ": DOWN tag not found");
        }

        $this->setUp($up);
        $this->setDown($down);
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return !(bool)$this->getId();
    }

    /**
     * @param null $newValue
     * @return bool
     */
    public function isRemove($newValue = null)
    {
        if (null !== $newValue) {
            $this->isRemove = (bool) $newValue;
        }
        return $this->isRemove;
    }

    /**
     * Выполняется в транзакции
     *
     * @param bool $newValue
     * @return bool
     */
    public function isTransaction($newValue = null)
    {
        if (null !== $newValue) {
            $this->isTransaction = (bool) $newValue;
        }
        return $this->isTransaction;
    }


    /**
     * UP
     *
     * @param string $up - SQL разделенный ";"
     */
    public function setUp($up)
    {
        $this->up = $this->_parse_sql($up);
    }

    /**
     * @return array - Массив SQL-запросов
     */
    public function getUp()
    {
        return $this->up;
    }


    /**
     * DOWN
     *
     * @param string $down - SQL разделенный ";"
     */
    public function setDown($down)
    {
        $this->down = $this->_parse_sql($down);
    }

    /**
     * @return array - Массив SQL-запросов
     */
    public function getDown()
    {
        return $this->down;
    }


    /**
     * Разобрать SQL на массив запросов
     *
     * @param  string $sql - SQL разделенный ";"
     * @return array - Массив SQL-запросов
     */
    private function _parse_sql($sql)
    {
        return array_values(array_filter(array_map(function ($value) { return trim(rtrim($value, $this->separator)); },
            preg_split(sprintf("/%s[\s]*\n/", preg_quote($this->separator)), rtrim(trim($sql), $this->separator)))));
    }
}
