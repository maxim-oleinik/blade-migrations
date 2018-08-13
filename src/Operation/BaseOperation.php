<?php namespace Blade\Migrations\Operation;

class BaseOperation implements \Psr\Log\LoggerAwareInterface, \Psr\Log\LoggerInterface
{
    use \Psr\Log\LoggerTrait,
        \Psr\Log\LoggerAwareTrait;

    public function log($level, $message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
