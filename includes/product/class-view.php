<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Product;

use Soisy\Client;
use SoisyPlugin\Includes\Helper;
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

    /**
     * View constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_soisy_product_loan_info_block', [&$this, 'soisy_product_loan_info_block']);
        add_action('wp_ajax_nopriv_soisy_product_loan_info_block', [&$this, 'soisy_product_loan_info_block']);
        add_action('woocommerce_single_product_summary', [&$this, 'add_soisy_script_to_product_view'], 50);
    }

    /**
     * Soisy template ajax response function
     */
    public function soisy_product_loan_info_block()
    {
        if (isset($_POST['price'])) {
            $this->init_payment_settings();

            $this->soisyClient = new Client(
                $this->settings['shop_id'],
                $this->settings['api_key'],
                $this->settings['sandbox_mode']
            );

            $loanAmount     = $_POST['price'] * 100;
            $loanSimulation = $this->soisyClient->getLoanSimulation([
                'amount'      => $loanAmount,
                'instalments' => Client::QUOTE_INSTALMENTS_AMOUNT,
            ]);
            if ($loanSimulation && isset($loanSimulation->median)) {
                $variables = [
                    '{INSTALMENT_AMOUNT}' => Helper::formatNumber($loanSimulation->median->instalmentAmount / 100),
                    '{INSTALMENT_PERIOD}' => Client::QUOTE_INSTALMENTS_AMOUNT,
                    '{TOTAL_REPAID}'      => Helper::formatNumber($loanSimulation->median->totalRepaid / 100),
                    '{TAEG}'              => Helper::formatNumber($loanSimulation->median->apr),
                ];

                wp_send_json([
                    'data'   => strtr(__('Loan quote text', 'soisy'), $variables),
                    'object' => Settings::LOAN_QUOTE_CSS_CLASS
                ]);
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