<?php namespace Blade\Migrations\Test;

use Psr\Log\AbstractLogger;


class TestLogger extends AbstractLogger
{
    private $log = [];

    public function log($level, $message, array $context = [])
    {
        $this->log[] = $message;
    }

    public function getLog()
    {
        return $this->log;
    }
}
