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

    public static function priceToRawNumber(string $price): float
    {
        return intval(preg_replace('/[^\d]+/', '', $price)) / 100;
    }
}