<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Soisy_Pagamento_Rateale
 * @subpackage Soisy_Pagamento_Rateale/includes
 * @author     Mirko Bianco <mirko@acmemk.com>
 */
class Soisy_Pagamento_Rateale_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'soisy-pagamento-rateale',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
