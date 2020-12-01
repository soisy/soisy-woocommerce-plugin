<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Product;

use Soisy\Client;
use SoisyPlugin\Includes\Settings;

class View
{

    /**
     * @var Client
     */
    protected $soisyClient;

    /**
     * Soisy Setting values.
     *
     * @var array
     */
    protected $settings;

    public function __construct()
    {
        add_action('woocommerce_single_product_summary', [&$this, 'add_soisy_loan_quote_tag'], 10);
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