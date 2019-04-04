<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Checkout\Cart;

use SoisyPlugin\Includes\Helper;
use SoisyPlugin\Includes\Log;
use Gateway;

class View {

    /**
     * @var Soisy_Client
     */
    protected $_client;

    /**
     * Soisy Setting values.
     * @var array
     */
    protected $settings;

    public function __construct()
    {
        add_action('wp_ajax_soisy_cart_loan_info_block', array(&$this, 'soisy_cart_loan_info_block'));
        add_action('wp_ajax_nopriv_soisy_cart_loan_info_block', array(&$this, 'soisy_cart_loan_info_block'));
        add_action( 'woocommerce_before_cart_table',  array(&$this, 'add_soisy_script_to_cart_view'), 50);
    }

    /**
     * Soisy template ajax response function
     */
    public function soisy_cart_loan_info_block()
    {
        if (isset($_POST['price'])) {
            $this->init_payment_settings();
            $this->_client = new \Client($this->settings['shop_id'], $this->settings['api_key'], new Log(),(int)$this->settings['sandbox_mode']);
            $loanAmount = $_POST['price']* 100;
            $amountResponse = $this->_client->getAmount(
                [
                    'amount' => $loanAmount,
                    'instalments' => \Client::QUOTE_INSTALMENTS_AMOUNT,
                ]);
            if ($amountResponse && isset($amountResponse->{'median'})) {
                $variables = array(
                    '{INSTALMENT_AMOUNT}' => Helper::formatNumber($amountResponse->{'median'}->instalmentAmount / 100),
                    '{INSTALMENT_PERIOD}' => \Client::QUOTE_INSTALMENTS_AMOUNT,
                    '{TOTAL_REPAID}' => Helper::formatNumber($amountResponse->{'median'}->totalRepaid / 100),
                    '{TAEG}' => Helper::formatNumber($amountResponse->{'median'}->apr),
                );

                wp_send_json(
                    array(
                        'data' => strtr(__('Cart loan quote text', 'soisy'), $variables),
                        'object' => Gateway::CART_LOAN_QUOTE_CSS_CLASS
                    )
                );
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
            $this->settings = get_option(Gateway::SETTINGS_OPTION_NAME, null);
        }
    }
}