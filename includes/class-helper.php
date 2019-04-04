<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes;

class Helper
{
    /**
     * Check if loan available
     * @param $order_total
     * @return bool
     */
    public static function isCorrectAmount($order_total)
    {
        return ($order_total >= \Bitbull_Soisy_Client::MIN_AMOUNT) && ($order_total <= \Bitbull_Soisy_Client::MAX_AMOUNT);
    }

    public static function formatNumber(float $number): string
    {
        return \number_format($number, 2, ',', '.');
    }
}