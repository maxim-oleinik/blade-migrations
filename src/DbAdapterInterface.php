<?php

interface DbAdapterInterface
{
    public function escape($sql);
    public function select($sql);
    public function execute($sql);
}