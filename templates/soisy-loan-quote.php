<?php
/**
 * Soisy Loan block template.
 *
 * @package     Soisy
 */
if (!defined('ABSPATH')) exit;

global $product;

$priceToCheck = (is_product()) ? ($product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price()) : WC()->cart->total;

if (\SoisyPlugin\Includes\Helper::isCorrectAmount($priceToCheck)):

?>
<soisy-loan-quote
        shop-id="<?=$this->getShopId(); ?>"
        amount="<?=$priceToCheck; ?>"
        instalments="<?=\Soisy\Client::QUOTE_INSTALMENTS_AMOUNT; ?>"></soisy-loan-quote>
<?php

endif;