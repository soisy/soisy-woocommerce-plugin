<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Product;

use Soisy\Client;
use SoisyPlugin\Includes\Helper;
use SoisyPlugin\Includes\Log;
use Gateway;
use SoisyPlugin\Includes\Settings;

class View
{
    /**
     * @var Client
     */
    protected $_client;

    /**
     * Soisy Setting values.
     * @var array
     */
    protected $settings;

    /**
     * View constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_soisy_product_loan_info_block', array(&$this, 'soisy_product_loan_info_block'));
        add_action('wp_ajax_nopriv_soisy_product_loan_info_block', array(&$this, 'soisy_product_loan_info_block'));
        add_action('woocommerce_single_product_summary', array(&$this, 'add_soisy_script_to_product_view'), 50);
    }

    /**
     * Soisy template ajax response function
     */
    public function soisy_product_loan_info_block()
    {
        if (isset($_POST['price'])) {
            $this->init_payment_settings();

            $this->_client = new Client(
                $this->settings['shop_id'],
                $this->settings['api_key'],
                (bool)$this->settings['sandbox_mode']
            );

            $loanAmount = $_POST['price'] * 100;
            $amountResponse = $this->_client->getAmount(
                [
                    'amount' => $loanAmount,
                    'instalments' => Client::QUOTE_INSTALMENTS_AMOUNT,
                ]);
            if ($amountResponse && isset($amountResponse->median)) {
                $variables = array(
                    '{INSTALMENT_AMOUNT}' => Helper::formatNumber($amountResponse->median->instalmentAmount / 100),
                    '{INSTALMENT_PERIOD}' => Client::QUOTE_INSTALMENTS_AMOUNT,
                    '{TOTAL_REPAID}'      => Helper::formatNumber($amountResponse->median->totalRepaid / 100),
                    '{TAEG}'              => Helper::formatNumber($amountResponse->median->apr),
                );

                wp_send_json(
                    array(
                        'data' => strtr(__('Loan quote text', 'soisy'), $variables),
                        'object' => Settings::LOAN_QUOTE_CSS_CLASS
                    )
                );
            }
        }
    }

    /**
     * Load soisy template in single product view
     */
    public function add_soisy_script_to_product_view()
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