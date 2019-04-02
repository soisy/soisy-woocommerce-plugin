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
    public static function check_if_method_available_by_amount($order_total)
    {
       return (\Bitbull_Soisy_Client::MIN_AMOUNT * 100 <= $order_total) || ($order_total <= \Bitbull_Soisy_Client::MAX_AMOUNT * 100);
    }

    public static function formatNumber(float $number): string
    {
        return \number_format($number, 2, ',', '.');
    }
}