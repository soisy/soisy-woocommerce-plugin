<?php

namespace Soisy\Log;

use Soisy\Exception;

/**
 * @package  Soisy
 */
interface LoggerInterface
{
    /**
     * Retrieve Soisy Log File
     *
     * @return string
     */
    public function getLogFile();

    /**
     * Logging facility
     *
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = null);

    /**
     * @param Exception $e
    */
    public function logException(Exception $e);

    /**
     * @param string $message
     */
    public function debug($message);
}