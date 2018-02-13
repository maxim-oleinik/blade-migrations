<?php namespace Usend\Migrations;


/**
 * @see \Usend\Migrations\Test\MigrationTest
 */
class Migration
{
    const TAG_BEGIN = '--BEGIN';
    const TAG_ROLLBACK = '--ROLLBACK';

    private $up = [];
    private $down = [];
    private $isRemove = false;

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

        preg_match('/--BEGIN(?:[\s]|$)/', $sql, $matches, PREG_OFFSET_CAPTURE);
        if (!$matches) {
            throw new \InvalidArgumentException(__METHOD__. ": UP tag not found");
        }
        $sql = trim(substr($sql, $matches[0][1] + strlen(self::TAG_BEGIN)));

        preg_match('/--ROLLBACK(?:[\s]|$)/', $sql, $matches, PREG_OFFSET_CAPTURE);
        if (!$matches) {
            throw new \InvalidArgumentException(__METHOD__. ": DOWN tag not found");
        }
        $up = trim(substr($sql, 0, $matches[0][1]));
        $down = trim(substr($sql, $matches[0][1] + strlen($matches[0][0])));

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
     * @param array $up
     */
    public function setUp($up)
    {
        $this->up = $this->_parse_sql($up);
    }

    /**
     * @param array $down
     */
    public function setDown($down)
    {
        $this->down = $this->_parse_sql($down);
    }

    private function _parse_sql($sql)
    {
        return array_values(array_filter(array_map('trim',
            preg_split("/;[\s]*\n/", rtrim(trim($sql), ';')))));
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
