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
    jQuery(document).ready(function ($) {
        var isProductPage = <?php echo (int)is_product(); ?>;
        var data = {
            action: '<?php echo $action ?>',
            price: '<?php echo (is_product()) ? $product->get_price() : WC()->cart->total ?>',
        };
        jQuery.ajax({
            type: "post",
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: data,

            success: function (data) {
                if (data.object) {

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
    });
</script>
