<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Checkout\Cart;

use Soisy\Client;
use SoisyPlugin\Includes\Settings;

class View
{

    /**
     * @var Client
     */
    protected $soisyClient;

    /**
     * @var array
     */
    protected $settings;

    public function __construct()
    {
        add_action('woocommerce_proceed_to_checkout', [&$this, 'add_soisy_loan_quote_tag']);
    }

    public function add_soisy_loan_quote_tag()
    {
        load_template(WC_SOISY_PLUGIN_PATH . '/templates/soisy-loan-quote.php');
    }

    /**
     * Init soisy payment settings
     */
    protected function init_payment_settings()
    {
        if (!isset($this->settings)) {
            $this->settings = get_option(Settings::OPTION_NAME, null);
        }
    }
}