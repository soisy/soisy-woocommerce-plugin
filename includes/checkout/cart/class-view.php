<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Checkout\Cart;

use Soisy\Client;
use SoisyPlugin\Includes\Helper;
use SoisyPlugin\Includes\Settings;

class View
{

    /**
     * @var Soisy_Client
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
        add_action('wp_ajax_soisy_cart_loan_info_block', [&$this, 'soisy_cart_loan_info_block']);
        add_action('wp_ajax_nopriv_soisy_cart_loan_info_block', [&$this, 'soisy_cart_loan_info_block']);
        add_action('woocommerce_before_cart_table', [&$this, 'add_soisy_script_to_cart_view'], 50);
    }

    /**
     * Soisy template ajax response function
     */
    public function soisy_cart_loan_info_block()
    {
        if (isset($_POST['price'])) {
            $this->init_payment_settings();
            $this->soisyClient = new Client(
                $this->settings['shop_id'],
                $this->settings['api_key'],
                $this->settings['sandbox_mode']
            );

            $loanAmount     = $_POST['price'] * 100;
            $simulationResponse = $this->soisyClient->getSimulation([
                'amount'      => $loanAmount,
                'instalments' => Client::QUOTE_INSTALMENTS_AMOUNT,
            ]);
            if ($simulationResponse && isset($simulationResponse->median)) {
                $variables = [
                    '{INSTALMENT_AMOUNT}' => Helper::formatNumber($simulationResponse->median->instalmentAmount / 100),
                    '{INSTALMENT_PERIOD}' => Client::QUOTE_INSTALMENTS_AMOUNT,
                    '{TOTAL_REPAID}'      => Helper::formatNumber($simulationResponse->median->totalRepaid / 100),
                    '{TAEG}'              => Helper::formatNumber($simulationResponse->median->apr),
                ];

                wp_send_json([
                    'data'   => strtr(__('Cart loan quote text', 'soisy'), $variables),
                    'object' => Settings::CART_LOAN_QUOTE_CSS_CLASS
                ]);
            }
        }
    }

    /**
     * Load soisy template in single product view
     */
    public function add_soisy_script_to_cart_view()
    {
        load_template(WC_SOISY_PLUGIN_PATH . '/templates/soisy-loan-block.php');
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