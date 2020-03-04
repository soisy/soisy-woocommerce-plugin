<?php
/**
 * Soisy Loan block template.
 *
 * @package     Soisy
 */
if (!defined('ABSPATH')) exit; // Don't allow direct access

$action = (is_product()) ? 'soisy_product_loan_info_block' : 'soisy_cart_loan_info_block';
?>

<?php global $product; ?>
<script>
    <?php
        $priceToCheck = is_product() ? $product->get_price() : WC()->cart->total;

        if (\SoisyPlugin\Includes\Helper::isCorrectAmount($priceToCheck)):
    ?>
    function updateSoisyInstalmentsQuote(data) {
        var isProductPage = <?php echo (int)is_product(); ?>;

        if (isProductPage) {
            jQuery('.summary .price').after("<p class='soisy-loading'>Calcolo pagamento rateale...</p>");
        } else {
            jQuery('.cart_totals .shop_table').after("<p class='soisy-loading'>Calcolo pagamento rateale...</p>");
        }


        jQuery.ajax({
            type: "post",
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: data,

            success: function (data) {
                if (data.object) {

                    jQuery('.'+data.object+', .soisy-loading').remove();

                    if (isProductPage) {
                        jQuery('.summary .price').after("<p class='" + data.object + "'>" + data.data + "</p>");
                        return;
                    }

                    jQuery('.cart_totals .shop_table').after("<p class='" + data.object + "'>" + data.data + "</p>");
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr);
            }
        });
    }

    function getCartTotal() {
        var totalWithCurrency = jQuery('.cart_totals .woocommerce-Price-amount').last().text();
        var total = totalWithCurrency
            .substr(0, totalWithCurrency.length - 1)
            .replace('.', '').replace(',', '.');

        return total;
    }

    jQuery(document).ready(function ($) {
        updateSoisyInstalmentsQuote({
            action: '<?php echo $action ?>',
            price: '<?php echo (is_product()) ? $product->get_price() : WC()->cart->total ?>',
        });
    });

    <?php if(!is_product()): ?>
    jQuery(document).on('click', 'button[name="update_cart"], .product-remove a, a.restore-item', function () {
        setTimeout(function () {
            updateSoisyInstalmentsQuote({
                action: '<?php echo $action ?>',
                price: getCartTotal(),
            });
        }, 2000);
    });
    <?php endif; ?>
    <?php endif; ?>
</script>
