<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes\Checkout;

use Bitbull_Soisy\Includes\Helper;
use Bitbull_Soisy\Includes\Log;

class SelectInstalments {

    /**
     * @var
     */
    protected $_client;

    /**
     * @var
     */
    protected $settings;

    /**
     * SelectInstalments constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_soisy_instalment_total_info_block', array(&$this, 'soisy_instalment_total_info_block'));
        add_action('wp_ajax_nopriv_soisy_instalment_total_info_block', array(&$this, 'soisy_instalment_total_info_block'));
    }

    /**
     * Ajax call action
     */
    public function soisy_instalment_total_info_block()
    {
        if (isset($_POST['instalments'])) {
            $loanAmount = WC()->cart->total * 100;
            $instalments = $_POST['instalments'];
            $this->init_payment_settings();

            if (Helper::check_if_method_available_by_amount($loanAmount)) {
                $this->_client = new \Bitbull_Soisy_Client($this->settings['shop_id'], $this->settings['api_key'], new Log(),(int)$this->settings['sandbox_mode']);
                $amountResponse = $this->_client->getAmount(
                    [
                        'amount' => $loanAmount,
                        'instalments' => $instalments,
                    ]);

                if ($amountResponse && isset($amountResponse->{'average'})) {

                    $variables = array(
                        'instalment_amount' => wc_price($amountResponse->{'average'}->instalmentAmount / 100),
                        'instalments_period' => wc_price($instalments),
                        'total_repaid' => wc_price($amountResponse->{'average'}->totalRepaid / 100),
                        'taeg' => wc_price($amountResponse->{'average'}->apr),
                    );

                    wp_send_json($variables);
                }
            } else {
                wp_send_json_error();
            }
        }
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