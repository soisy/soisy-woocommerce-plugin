<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes;

use Soisy\Client;

class Helper
{
    /**
     * Check if loan available
     * @param $order_total
     * @return bool
     */
    public static function isCorrectAmount($order_total)
    {
        return ($order_total >= Client::MIN_AMOUNT) && ($order_total <= Client::MAX_AMOUNT);
    }

    public static function formatNumber(float $number): string
    {
        return \number_format($number, 2, ',', '.');
    }

    public static function isSoisyGatewayPaymentActive(): bool
    {
        $gateways = apply_filters( 'woocommerce_payment_gateways', []);

        foreach ($gateways as $gateway) {
            if ($gateway == 'SoisyGateway') {
                return (new $gateway())->enabled === 'yes';
            }
        }

        return false;
    }
}