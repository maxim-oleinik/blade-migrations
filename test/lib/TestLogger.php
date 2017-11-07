<?php namespace Test;

use Psr\Log\AbstractLogger;


class TestLogger extends AbstractLogger {
    private $log = [];
    public function log($level, $message, array $context = array())
    {
        $this->log[] = $message;
    }
    public function getLog()
    {
        return $this->log;
    }
}
