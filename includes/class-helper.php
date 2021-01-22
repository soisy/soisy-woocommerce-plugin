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

    public static function htmlPriceToNumber(string $price): float
    {
        $price = strip_tags($price);
        $price = self::cleanPriceByChar('€', $price);
        $price = self::cleanPriceByChar(' ', $price);
        $price = preg_replace('/[^\d,\.]+/', '', $price);

        if (self::hasDecimals($price)) {
            $char = self::getDecimalPointChar($price);

            return floatval(implode('.', explode($char, $price)));
        }

        return intval($price);
    }

    public static function cleanPriceByChar(string $character, string $price): string
    {
        $parts = explode($character, $price);

        foreach ($parts as $i => $part) {
            $parts[$i] = trim($part);
        }

        return implode($character, array_unique($parts));
    }

    public static function hasDecimals(string $price): bool
    {
        $char = self::getDecimalPointChar($price);

        if (!empty($char)) {
            $priceParts = explode($char, $price);
            if (!empty($priceParts[1]) && intval($priceParts[1]) >= 0) {
                return true;
            }
        }

        return false;
    }

    public static function getDecimalPointChar(string $price): string
    {
        $dotPos = strpos($price, '.');
        $commaPos = strpos($price, ',');

        if ($dotPos === false && $commaPos === false) {
            return '';
        }

        if ($dotPos === false && $commaPos !== false) {
            return $commaPos;
        }

        if ($dotPos !== false && $commaPos === false) {
            return $dotPos;
        }

        return $dotPos < $commaPos ? $commaPos : $dotPos;
    }

    public static function isSoisyLoanQuoteCalculatedAlready(string $price): bool
    {
        return strpos($price, '<soisy-loan-quote') !== false;
    }
}