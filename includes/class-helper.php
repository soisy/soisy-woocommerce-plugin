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
     * @param $min_order_total
     * @param $max_order_total
     * @param $order_total
     * @return bool
     */
    public static function check_if_method_available_by_amount($min_order_total,$max_order_total,$order_total)
    {
       return ((int)$min_order_total * 100 <= $order_total) || ($order_total <= (int)$max_order_total * 100);
    }
}