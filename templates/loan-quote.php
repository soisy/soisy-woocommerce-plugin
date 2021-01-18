<?php
/**
 * Soisy Loan block template.
 *
 * @package     Soisy
 */
if (!defined('ABSPATH')) exit;

use Soisy\SoisyClient;

global $product;

if (is_product()) {

    switch ($product->get_type()) {
        default:
        case 'simple':
            $priceToCheck = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();
            break;

        case 'grouped':
            $priceToCheck = $product->is_on_sale() ? $product->get_sale_price() : $product->get_price();
            break;

        case 'variable':
            $priceToCheck = $product->is_on_sale() ? $product->get_variation_sale_price() : $product->get_variation_regular_price();
            break;
    }

} else {
    $priceToCheck = WC()->cart->total;
}

if (\Soisy\Includes\Helper::isCorrectAmount($priceToCheck)):

?>
    <br>
    <soisy-loan-quote
        shop-id="<?=$this->getShopId(); ?>"
        amount="<?=$priceToCheck; ?>"
        instalments="<?=SoisyClient::QUOTE_INSTALMENTS_AMOUNT; ?>"></soisy-loan-quote>
    <br>
    <br>
<?php

endif;