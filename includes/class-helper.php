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
     * Return instalment period from select
     * @return mixed
     */
    public static function get_min_max_instalment_period($instalmentPeriodArray)
    {
        if (min($instalmentPeriodArray) != max($instalmentPeriodArray)) {
            return [min($instalmentPeriodArray), max($instalmentPeriodArray)];
        } else {
            return [min($instalmentPeriodArray)];
        }
    }

    /**
     * @param $amount
     * @return int
     */
    public static function get_default_instalment_period_by_amount_from_table($amount)
    {
        $instalmentTable = self::init_instalment_table_settings();
        if (is_array($instalmentTable)) {
            $lastItem = null;

            usort($instalmentTable, function ($a, $b) {
                return (int)$a['amount'] - $b['amount'];
            });

            for ($i = 0; $i < count($instalmentTable); $i++) {
                if (((int)($instalmentTable[$i]['amount'] * 100) <= $amount) && isset($instalmentTable[$i + 1]) && (((int)$instalmentTable[$i + 1]['amount'] * 100) > $amount)) {
                    return (int)($instalmentTable[$i]['period']);
                } else {
                    if (!isset($instalmentTable[$i + 1])) {
                        return (int)($instalmentTable[$i]['period']);
                    }
                }
            }
        }
    }

    /**
     * @return mixed|void
     */
    public static function init_instalment_table_settings()
    {
        return get_option(\Bitbull_Soisy_Gateway::INSTALMENT_TABLE_OPTION_NAME, null);
    }

    /**
     * Calculate amount based on percentage
     * @param $amount
     * @param $percentage
     * @return mixed
     */
    public static function  calculate_amount_based_on_percentage($amount,$percentage)
    {
        return $amount - ($amount / 100) * ($percentage);
    }

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