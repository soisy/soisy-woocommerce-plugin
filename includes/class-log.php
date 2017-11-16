<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes;

//TODO : Need to implement real log to WC file.

class Log implements \Bitbull_Soisy_Log_LoggerInterface
{

    /**
     * @var WC_Logger
     */
    protected $log;

    /**
     * Log constructor.
     */
    public function __construct()
    {
        $this->log = new \WC_Logger();
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
        $this->log->add( 'woocommerce-gateway-soisy', $message, $level);
    }

    /**
     * @param Exception $e
     */
    public function logException(\Exception $e)
    {
        $this->log->add( 'woocommerce-gateway-soisy', $e);
    }

    /**
     * @param string $message
     */
    public function debug($message)
    {
        $this->log->add( 'woocommerce-gateway-soisy', $message);
    }
}