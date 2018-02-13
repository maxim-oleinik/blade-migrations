<?php namespace Usend\Migrations;

/**
 * @see \Usend\Migrations\Test\MigrationTest
 */
class Migration
{
    const TAG_BEGIN     = '--BEGIN';
    const TAG_ROLLBACK  = '--ROLLBACK';
    const TAG_UP        = '--UP';
    const TAG_DOWN      = '--DOWN';

    private $up = [];
    private $down = [];
    private $isRemove = false;
    private $isTransaction = true;

    private $id;
    private $name;
    private $date;
    private $sql;


    /**
     * Конструктор
     *
     * @param      $id
     * @param      $name
     * @param      $date
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

        preg_match_all('/--[A-Z]+(?:[\s]|$)/', $sql, $matches, PREG_OFFSET_CAPTURE);

        $up = null;
        $down = null;

        $tagDown = self::TAG_ROLLBACK;
        $posUp = null;
        $posDown = null;

        foreach ($matches[0] as $data) {
            list($tag, $position) = $data;
            $tag = trim($tag);

            switch (trim($tag)) {
                case self::TAG_UP:
                    $this->isTransaction = false;
                    $tagDown = self::TAG_DOWN;
                case self::TAG_BEGIN:
                    if (null !== $posUp) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected single {$tag} tag");
                    }
                    $posUp = $position + strlen($tag);
                    break;

                case self::TAG_ROLLBACK:
                case self::TAG_DOWN:
                    if (null === $posUp) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected BEGIN/UP tag first");
                    } elseif ($tag != $tagDown) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected `{$tagDown}`, got `{$tag}` tag");
                    } elseif (null !== $posDown) {
                        throw new \InvalidArgumentException(__METHOD__. ": Expected single {$tag} tag");
                    }
                    $posDown = $position + strlen($tag);
                    $up   = trim(substr($sql, $posUp, $position - $posUp));
                    $down = trim(substr($sql, $posDown));
                    break;
            }
        }

        if (null === $posUp) {
            throw new \InvalidArgumentException(__METHOD__. ": BEGIN/UP tag not found");
        }
        if (null === $posDown) {
            throw new \InvalidArgumentException(__METHOD__. ": ROLLBACK/DOWN tag not found");
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
        return array_values(array_filter(array_map('trim',
            preg_split("/;[\s]*\n/", rtrim(trim($sql), ';')))));
    }
}
