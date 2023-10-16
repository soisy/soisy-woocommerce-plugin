<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.soisy.it
 * @package           Soisy_Pagamento_Rateale
 *
 * @wordpress-plugin
 * Plugin Name:       Soisy Pagamento Rateale
 * Plugin URI:        https://doc.soisy.it/it/Plugin/WooCommerce.html
 * Description:       Soisy, la piattaforma di prestiti p2p che offre ai tuoi clienti il pagamento a rate.
 * Version:           6.0.1
 * Author: Soisy
 * Author URI: https://www.soisy.it
 * License: MIT
 * Text Domain:       soisy-pagamento-rateale
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SOISY_PAGAMENTO_RATEALE_VERSION', '6.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-soisy-pagamento-rateale-activator.php
 */
function activate_soisy_pagamento_rateale() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-soisy-pagamento-rateale-activator.php';
	Soisy_Pagamento_Rateale_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-soisy-pagamento-rateale-deactivator.php
 */
function deactivate_soisy_pagamento_rateale() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-soisy-pagamento-rateale-deactivator.php';
	Soisy_Pagamento_Rateale_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_soisy_pagamento_rateale' );
register_deactivation_hook( __FILE__, 'deactivate_soisy_pagamento_rateale' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
	require plugin_dir_path(__FILE__) . 'includes/class-soisy-pagamento-rateale.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 */
function run_soisy_pagamento_rateale() {

	$plugin = new Soisy_Pagamento_Rateale();
	$plugin->run();

}

function settings_soisy_pagamento_rateale()
{
    return apply_filters('soisy_settings', []);
}

function isSoisyAvailable(){
	return apply_filters('soisy_available', true);
}

run_soisy_pagamento_rateale();
