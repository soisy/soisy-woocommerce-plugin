<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes\Checkout;

use Gateway;
use Soisy\Client;
use SoisyPlugin\Includes\Helper;
use SoisyPlugin\Includes\Log;
use SoisyPlugin\Includes\Settings;

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

            if (Helper::isCorrectAmount($loanAmount)) {
                $this->_client = new Client(
                    $this->settings['shop_id'],
                    $this->settings['api_key'],
                    (bool)$this->settings['sandbox_mode']
                );

                $amountResponse = $this->_client->getAmount(
                    [
                        'amount' => $loanAmount,
                        'instalments' => $instalments,
                    ]);

                if ($amountResponse && isset($amountResponse->median)) {

                    $variables = array(
                        'instalment_amount' => wc_price($amountResponse->median->instalmentAmount / 100),
                        'instalments_period' => wc_price($instalments),
                        'total_repaid' => wc_price($amountResponse->median->totalRepaid / 100),
                        'taeg' => wc_price($amountResponse->median->apr),
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
            $this->settings = get_option(Settings::OPTION_NAME, null);
        }
    }
}