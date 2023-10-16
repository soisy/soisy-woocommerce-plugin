<?php
	
	/**
	 * Prints the HTML content in the checkout payment gateways group
	 *
	 * @link       https://acmemk.com
	 * @since      1.0.0
	 *
	 * @package    Soisy_Pagamento_Rateale
	 * @subpackage Soisy_Pagamento_Rateale/public/partials
	 */
	
	global $soisyId;
	$opt         = settings_soisy_pagamento_rateale();
	$td = $opt['textdomain'];
	
	$soisyCheck = apply_filters( 'soisy_agree_tos', [] );
	$gwValues = apply_filters( 'soisy_additional_values', [] );
	
	wp_enqueue_script('woocommerce_checkout_instalment_select');
?>
<p><?php echo __('Spread the cost with Soisy', $td); ?></p>

<?php do_action( 'soisy_render_widget', true ); ?>
<fieldset id="<?php echo esc_attr($soisyId); ?>-soisy-form" class='wc-check-form wc-payment-form'>
	<?php do_action('woocommerce_echeck_form_start', $soisyId); ?>
	<?php
		foreach ( $soisyCheck as $key => $item ) {
			$value = isset( $gwValues[ $key ] ) ? $gwValues[ $key ] : '';
			woocommerce_form_field( $key, $item, $value );
		}
	?>
	<?php do_action('woocommerce_echeck_form_end', $this->id); ?>
    <div class="clear"></div>
</fieldset>
