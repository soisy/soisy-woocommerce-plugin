<?php
/**
 * Plugin Name: Soisy Payment Gateway
 * Plugin URI: https://doc.soisy.it/it/Plugin/WooCommerce.html
 * Description: Soisy, the a P2P lending platform that allows your customers to pay in instalments.
 * Version: 2.0.0
 * Author: Soisy
 * Author URI: https://www.soisy.it
 * Text Domain: soisy
 * Domain Path: /languages
 * License: MIT
 */

/**
 * Check if WooCommerce is active
 **/
if ((!defined('ABSPATH')) && (!in_array('woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))))) {
    exit;
}

use Soisy\Client;
use SoisyPlugin\Includes;

define('WC_SOISY_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

require_once(trailingslashit(dirname(__FILE__)) . '/includes/autoloader.php');

function woo_payment_gateway()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class SoisyGateway extends WC_Payment_Gateway
    {

        /**
         * @var array $available_country ;
         */
        protected $available_country = ['IT'];

        /**
         * @var $client ;
         */
        protected $client;

        /**
         * Soisy_Gateway constructor.
         */
        public function __construct()
        {
            $plugin_dir         = plugin_dir_url(__FILE__);
            $this->id           = 'soisy';
            $this->method_title = __('Soisy', 'soisy');
            $this->icon         = apply_filters('woocommerce_Soisy_icon', '' . $plugin_dir . '/assets/images/' . 'logo-soisy-min.png');

            $this->supports    = ['soisy_payment_form'];
            $this->has_fields  = true;
            $this->form_fields = [];

            $this->init_form_fields();
            $this->init_settings();

            $this->title              = __('Soisy', 'soisy');
            $this->method_title       = __('Soisy', 'soisy');
            $this->method_description = __('WooCommerce Payment Gateway for Soisy.it', 'soisy');
            $this->success_message    = "Thanks for choosing Soisy";
            $this->msg['message']     = "";
            $this->msg['class']       = "";

            add_filter('woocommerce_available_payment_gateways', [&$this, 'payment_gateway_disable_country']);

            add_filter('woocommerce_available_payment_gateways', [&$this, 'payment_gateway_disable_by_amount']);

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [&$this, 'process_admin_options']);
            } else {
                add_action('woocommerce_update_options_payment_gateways', [&$this, 'process_admin_options']);
            }

            // Hooks
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
            add_action('woocommerce_after_checkout_form', [&$this, 'checkout_enqueue_scripts']);
        }

        /**
         * Check if payment available in client billing country
         *
         * @param $available_gateways
         *
         * @return mixed
         */
        public function payment_gateway_disable_country($available_gateways)
        {
            if (empty(WC()->customer) || empty(WC()->customer->get_billing_country())) {
                return;
            }

            if (isset($available_gateways['soisy']) && !in_array(WC()->customer->get_billing_country(), $this->available_country)) {
                unset($available_gateways['soisy']);
            }

            return $available_gateways;
        }

        /**
         * Check if Soisy available min/max available amount
         *
         * @param $available_gateways
         *
         * @return mixed
         */
        public function payment_gateway_disable_by_amount($available_gateways)
        {

            $order_total = SoisyGateway::get_order_total();

            if (isset($available_gateways['soisy']) && ((Client::MIN_AMOUNT > $order_total) || ($order_total >= Client::MAX_AMOUNT))) {
                unset($available_gateways['soisy']);
            }

            return $available_gateways;
        }

        /**
         * Admin setting fields
         */
        public function init_form_fields()
        {
            $this->form_fields = Includes\Settings::adminSettingsForm();
        }

        /**
         * Admin options
         */
        public function admin_options()
        {
            $this->instance_options();
        }

        public function get_form_data()
        {
            return get_option($this->get_option_key() . '_instalment_table', null);
        }

        /**
         * Set up Soisy checkout fields
         */
        public function payment_fields()
        {
            if ($this->supports('soisy_payment_form') && is_checkout()) {
                $this->form();
            }
        }

        /**
         * admin_options function.
         */
        public function instance_options()
        {
            ?>
            <table class="form-table">
                <?php
                $this->generate_settings_html();
                ?>
            </table>
            <?php
        }


        /**
         * Outputs fields for entering Soisy information.
         *
         * @since 2.6.0
         */
        public function form()
        {
            wp_enqueue_script('woocommerce_checkout_instalment_select');
            ?>
            <p><?php echo __('Soisy checkout description', 'soisy'); ?></p>
            <fieldset id="<?php echo esc_attr($this->id); ?>-soisy-form" class='wc-check-form wc-payment-form'>
                <?php do_action('woocommerce_echeck_form_start', $this->id); ?>
                <?php
                foreach (Includes\Settings::checkoutForm($this->id) as $key => $field) :
                    woocommerce_form_field($key, $field,
                        Includes\Settings::getCheckoutFormFieldValueByKey($this->id, $key));
                endforeach; ?>
                <?php do_action('woocommerce_echeck_form_end', $this->id); ?>
                <div class="clear"></div>
            </fieldset>
            <?php
        }

        /**
         * Helper function to display error for different version of Woocommerce
         *
         * @param $message
         */
        public function displayErrorMessage($message)
        {
            if ($this->getWoocommerceVersionNumber() >= 2.1) {
                return wc_add_notice(__($message, 'soisy'), 'error');
            }

            return WC()->add_error(__($message, 'soisy'));
        }

        /**
         * Process the payment and return the result
         **/
        public function process_payment($order_id)
        {
            $this->client = new Client(
                $this->settings['shop_id'],
                $this->settings['api_key'],
                $this->settings['sandbox_mode']
            );

            $order = new WC_Order($order_id);

            $amount = WC()->cart->total * 100;

            $params = [
                'firstname'   => $order->get_billing_first_name(),
                'lastname'    => $order->get_billing_last_name(),
                'email'       => $order->get_billing_email(),
                'city'        => $_POST['billing_city'],
                'address'     => $_POST['billing_address_1'],
                'civicNumber' => $_POST['billing_address_2'],
                'postalCode'  => $_POST['billing_postcode'],
                'province'    => $_POST['billing_state'],
                'mobilePhone' => $_POST['billing_phone'],
                'amount'      => $amount,
                'instalments' => $_POST['soisy-instalment'],
                'fiscalCode'  => $_POST['soisy-fiscal-code'],
            ];

            try {
                $token = $this->client->requestToken($params);

                if (is_null($token)) {
                    throw new \Error('Token unavailable. Request failed.');
                }

                WC()->session->set('soisy_token', $token);

                if ($order->status !== 'completed') {
                    $order->update_status('on-hold');
                    WC()->cart->empty_cart();

                    $order->add_order_note($this->success_message . ' Transaction ID: ');
                    unset($_SESSION['order_awaiting_payment']);
                }

                return [
                    'result'   => 'success',
                    'redirect' => $this->client->getRedirectUrl(WC()->session->get('soisy_token')),
                ];
            } catch (\DomainException $e) {
                $errorMessage = sprintf("%s: %s", __('Validation error', 'soisy'), $e->getMessage());

                $order->add_order_note($errorMessage);
                $order->update_status('failed');

                wc_add_notice($errorMessage);
            } catch (\Error $e) {
                $errorMessage = sprintf("%s: %s", __('HTTP request error', 'soisy'), $e->getMessage());

                $order->add_order_note($errorMessage);
                $order->update_status('failed');

                wc_add_notice($errorMessage);
            }
        }

        /**
         * Validate payment fields
         */
        public function validate_fields()
        {
            //Fix for validating checkbox
            if (!isset($_POST['soisy-checkbox'])) {
                $_POST['soisy-checkbox'] = '';
            }

            foreach ($_POST as $key => $value) {
                if (strpos($key, 'soisy') === 0) {
                    if (!($_POST[$key])) {
                        wc_add_notice('<strong>' . ucfirst(str_replace("-", " ",
                                $key)) . '</strong> ' . __('is a required field.', 'soisy'), 'error');
                    }
                }
            }
        }

        public function admin_enqueue_scripts()
        {

        }

        public function checkout_enqueue_scripts()
        {

        }
    }
}

/**
 * Add Gateway class to all payment gateway methods
 */
function woo_add_gateway_class($methods)
{
    $methods[] = 'SoisyGateway';

    return $methods;
}

add_filter('woocommerce_payment_gateways', 'woo_add_gateway_class');

add_action('plugins_loaded', 'woo_payment_gateway', 0);


function my_plugin_load_plugin_textdomain()
{
    load_plugin_textdomain('soisy', false, basename(dirname(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'my_plugin_load_plugin_textdomain');


/**
 * Adds soisy loan info on product page
 */
function init_product_page()
{
    new \SoisyPlugin\Includes\Product\View();
    new \SoisyPlugin\Includes\Checkout\Cart\View();
    new \SoisyPlugin\Includes\Checkout\SelectInstalments();
}

add_action('plugins_loaded', 'init_product_page');
