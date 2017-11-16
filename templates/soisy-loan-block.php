<?php
/**
 * Soisy Loan block template.
 *
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 * @version     0.0.1
 */
if (!defined('ABSPATH')) exit; // Don't allow direct access
$action = (is_product()) ?  'soisy_product_loan_info_block' : 'soisy_cart_loan_info_block';
?>

<?php global $product; ?>
<script>
    jQuery(document).ready(function ($) {
        var data = {
            action: '<?php echo $action ?>',
            price: '<?php echo (is_product()) ?  $product->get_price() : WC()->cart->total ?>',
        };
        jQuery.ajax({
            type: "post",
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: data,

            success: function (data) {
                if (data.object) {
                    jQuery("<p>" + data.data + "</p>").insertAfter("." + data.object);
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr);
            }
        });
    });
</script>
