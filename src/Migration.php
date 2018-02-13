<?php namespace Usend\Migrations;


/**
 * @see \Usend\Migrations\Test\MigrationTest
 */
class Migration
{
    const TAG_BEGIN = '--BEGIN';
    const TAG_ROLLBACK = '--ROLLBACK';
    const TAG_UP = '--UP';
    const TAG_DOWN = '--DOWN';

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

        preg_match_all('/--[A-Z]+(?:[\s]|$)/', $sql, $matches, PREG_OFFSET_CAPTURE);

        $up = null;
        $down = null;

        $isTransaction = false;
        $tagDown = self::TAG_DOWN;
        $posUp = null;
        $posDown = null;

        foreach ($matches[0] as $data) {
            list($tag, $position) = $data;
            $tag = trim($tag);

            switch (trim($tag)) {
                case self::TAG_BEGIN:
                    $isTransaction = true;
                    $tagDown = self::TAG_ROLLBACK;
                case self::TAG_UP:
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
     * @param string $up
     */
    public function setUp($up)
    {
        $this->up = $this->_parse_sql($up);
    }

    /**
     * @param string $down
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
