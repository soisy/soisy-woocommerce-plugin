<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes\Product;

use Bitbull_Soisy\Includes\Helper;
use Bitbull_Soisy\Includes\Log;

class View
{
    /**
     * @var Bitbull_Soisy_Client
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
            $this->_client = new \Bitbull_Soisy_Client($this->settings['shop_id'], $this->settings['api_key'], new Log(),(int)$this->settings['sandbox_mode']);
            $loanAmount = $_POST['price']* 100;
            $instalmentPeriodFromTable = Helper::get_default_instalment_period_by_amount_from_table($loanAmount);
            $instalmentPeriod = ($instalmentPeriodFromTable) ? $instalmentPeriodFromTable : Helper::get_instalment_period($this->settings['instalments_period'])  ;
            $amountResponse = $this->_client->getAmount(
                [
                    'amount' => $loanAmount,
                    'instalments' => $instalmentPeriod,
                ]);
            if ($amountResponse && isset($amountResponse->{$this->settings['information_about_loan']})) {
                $variables = array(
                    '{INSTALMENT_AMOUNT}' => $amountResponse->{$this->settings['information_about_loan']}->instalmentAmount / 100,
                    '{INSTALMENT_PERIOD}' => $instalmentPeriod,
                    '{TOTAL_REPAID}' => $amountResponse->{$this->settings['information_about_loan']}->totalRepaid / 100,
                    '{TAEG}' => $amountResponse->{$this->settings['information_about_loan']}->apr,
                );

                wp_send_json(
                    array(
                        'data' => strtr($this->settings['loan_quote_text'], $variables),
                        'object' => $this->settings['loan_quote_placement']
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
            $this->settings = get_option(\Bitbull_Soisy_Gateway::SETTINGS_OPTION_NAME, null);
        }
    }
}