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
        if (stripos($price, '</del>') !== false) {
            $price = explode('</del>', $price)[1];
        }

        $price = strip_tags($price);
        $price = self::cleanPriceByChar('€', $price);
        $price = self::cleanPriceByChar(' ', $price);
        $price = preg_replace('/[^\d,\.]+/', '', $price);

        if (self::hasDecimals($price)) {
            return self::getFloatValue($price);
        }

        return floatval(preg_replace('/[^\d]/', '', $price));
    }

    public static function getFloatValue(string $price): float
    {
        return intval(preg_replace('/[^\d]/', '', $price)) / 100;
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
            if (isset($priceParts[1]) && $priceParts[1] !== '' && intval($priceParts[1]) >= 0) {
                return true;
            }
        }

        return false;
    }

    public static function getDecimalPointChar(string $price): string
    {
        if ($price[strlen($price)-1] === '.' || $price[strlen($price)-1] === ',') {
            $price = substr($price, 0, strlen($price)-1);
        }

        $dotPos = strpos($price, '.');
        $commaPos = strpos($price, ',');
        $decimalSeparatorPos = strlen($price) - 3;

        if ($dotPos === false && $commaPos === false) {
            return '';
        }

        if ($dotPos === false && $commaPos !== false) {
            if ($commaPos === $decimalSeparatorPos) {
                return ',';
            }

            return '';
        }

        if ($dotPos !== false && $commaPos === false) {
            if ($dotPos === $decimalSeparatorPos) {
                return '.';
            }

            return '';
        }

        return $dotPos < $commaPos ? ',' : '.';
    }

    public static function isSoisyLoanQuoteCalculatedAlready(string $price): bool
    {
        return strpos($price, '<soisy-loan-quote') !== false;
    }
}