<?php

namespace SoisyPlugin\Includes;

use Soisy\Exception;
use Soisy\Log\LoggerInterface;

class Log implements LoggerInterface
{

    protected $log;

    /**
     * Log constructor.
     */
    public function __construct($logger)
    {
        $this->log = $logger;
    }

    /**
     * Retrieve Soisy Log File
     *
     * @return string
     */
    public function getLogFile()
    {
        return null;
    }

    /**
     * Logging facility
     *
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = null)
    {
        $this->log->add('woocommerce-gateway-soisy', $message, $level);
    }

    /**
     * @param Exception $e
     */
    public function logException(Exception $e)
    {
        $this->log->add('woocommerce-gateway-soisy', $e);
    }

    /**
     * @param string $message
     */
    public function debug($message)
    {
        $this->log->add('woocommerce-gateway-soisy', $message);
    }
}