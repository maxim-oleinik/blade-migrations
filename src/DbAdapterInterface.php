<?php

interface DbAdapterInterface
{
    public function select($sql);
    public function execute($sql);
}