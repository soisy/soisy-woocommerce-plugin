<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes\Checkout\Cart;

use Bitbull_Soisy\Includes\Helper;
use Bitbull_Soisy\Includes\Log;

class View {

    /**
     * @var Bitbull_Soisy_Client
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
            $this->_client = new \Bitbull_Soisy_Client($this->settings['shop_id'], $this->settings['api_key'], new Log(),(int)$this->settings['sandbox_mode']);
            $loanAmount = $_POST['price']* 100;
            $amountResponse = $this->_client->getAmount(
                [
                    'amount' => $loanAmount,
                    'instalments' => \Bitbull_Soisy_Client::QUOTE_INSTALMENTS_AMOUNT,
                ]);
            if ($amountResponse && isset($amountResponse->{'average'})) {
                $variables = array(
                    '{INSTALMENT_AMOUNT}' => $amountResponse->{'average'}->instalmentAmount / 100,
                    '{INSTALMENT_PERIOD}' => \Bitbull_Soisy_Client::QUOTE_INSTALMENTS_AMOUNT,
                    '{TOTAL_REPAID}' => $amountResponse->{'average'}->totalRepaid / 100,
                    '{TAEG}' => $amountResponse->{'average'}->apr,
                );

                wp_send_json(
                    array(
                        'data' => strtr($this->settings['cart_loan_quote_text'], $variables),
                        'object' => $this->settings['cart_loan_quote_placement']
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
            $this->settings = get_option(\Bitbull_Soisy_Gateway::SETTINGS_OPTION_NAME, null);
        }
    }

}