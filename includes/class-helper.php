<?php
/**
 * @package  Soisy
 */

namespace Soisy\Includes;

use Soisy\SoisyClient;

class Helper
{
    public static function isCorrectAmount($order_total): bool
    {
        return ($order_total >= SoisyClient::MIN_AMOUNT) && ($order_total <= SoisyClient::MAX_AMOUNT);
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