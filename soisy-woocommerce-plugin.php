<?php
/**
 * Plugin Name: Soisy Payment Gateway
 * Plugin URI: https://doc.soisy.it/it/Plugin/WooCommerce.html
 * Description: Soisy, the a P2P lending platform that allows your customers to pay in instalments.
 * Version: 4.1.0
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

function init_soisy()
{
    class SoisyGateway extends WC_Payment_Gateway
    {
        /** @var array $availableCountries */
        protected $availableCountries = ['IT'];

        /** @var Client $client */
        protected $client;

        public function __construct()
        {
            $this->id           = 'soisy';
            $this->icon         = apply_filters('woocommerce_Soisy_icon',  plugin_dir_url(__FILE__) . '/assets/images/logo-soisy-min.png');

            $this->supports    = ['soisy_payment_form'];
            $this->has_fields  = true;
            $this->form_fields = [];

            $this->init_form_fields();
            $this->init_settings();

            $this->title              = __('Pay in instalments with Soisy', 'soisy');
            $this->method_title       = __('Soisy', 'soisy');
            $this->method_description = __('Allow your customers to pay in instalments with Soisy, the P2P lending payment method', 'soisy');
            $this->success_message    = "Thanks for choosing Soisy";
            $this->msg['message']     = "";
            $this->msg['class']       = "";

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [&$this, 'process_admin_options']);
            } else {
                add_action('woocommerce_update_options_payment_gateways', [&$this, 'process_admin_options']);
            }

            add_filter('woocommerce_available_payment_gateways', [&$this, 'payment_gateway_disable_countries']);
            add_filter('woocommerce_available_payment_gateways', [&$this, 'payment_gateway_disable_by_amount']);

            $this->soisyWidgetInit();
        }

        public function soisyWidgetInit()
        {
            add_action('woocommerce_single_product_summary', [&$this, 'add_soisy_loan_quote_widget_js']);
            add_action('woocommerce_single_product_summary', [&$this, 'add_soisy_loan_quote_widget_tag'], 10);

            add_action('woocommerce_proceed_to_checkout', [&$this, 'add_soisy_loan_quote_widget_js']);
            add_action('woocommerce_proceed_to_checkout', [&$this, 'add_soisy_loan_quote_widget_tag']);

            add_filter('script_loader_tag', [&$this, 'make_script_async'], 10, 3);
        }

        public function add_soisy_loan_quote_widget_tag()
        {
            require_once( __DIR__ . '/templates/soisy-loan-quote.php');
        }

        public function add_soisy_loan_quote_widget_js()
        {
            wp_enqueue_script('soisy-loan-quote-widget', 'https://cdn.soisy.it/loan-quote-widget.js', [], null, true);
        }

        function make_script_async( $tag, $handle, $src )
        {
            if ( 'soisy-loan-quote-widget' != $handle ) {
                return $tag;
            }

            return str_replace( '<script', '<script async defer', $tag );
        }

        public function payment_gateway_disable_countries($available_gateways)
        {
            if (empty(WC()->customer) || empty(WC()->customer->get_billing_country())) {
                return $available_gateways;
            }

            if (isset($available_gateways['soisy']) && !in_array(WC()->customer->get_billing_country(), $this->availableCountries)) {
                unset($available_gateways['soisy']);
            }

            return $available_gateways;
        }

        public function payment_gateway_disable_by_amount($available_gateways)
        {

            $order_total = SoisyGateway::get_order_total();

            if (isset($available_gateways['soisy']) && ($order_total < Client::MIN_AMOUNT || $order_total > Client::MAX_AMOUNT)) {
                unset($available_gateways['soisy']);
            }

            return $available_gateways;
        }

        public function init_form_fields()
        {
            $this->form_fields = Includes\Settings::adminSettingsForm();
        }

        public function admin_options()
        {
            $this->instance_options();
        }

        public function get_form_data()
        {
            return get_option($this->get_option_key() . '_instalment_table', null);
        }

        public function payment_fields()
        {
            if ($this->supports('soisy_payment_form') && is_checkout()) {
                $this->form();
            }
        }

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

        public function form()
        {
            wp_enqueue_script('woocommerce_checkout_instalment_select');
            ?>
            <p><?php echo __('Soisy checkout description', 'soisy'); ?></p>
            <div>
                <script async defer src="https://cdn.soisy.it/loan-quote-widget.js"></script>
                <?php
                    $this->add_soisy_loan_quote_widget_tag();
                ?>
            </div>
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

        public function displayErrorMessage($message)
        {
            if ($this->getWoocommerceVersionNumber() >= 2.1) {
                return wc_add_notice(__($message, 'soisy'), 'error');
            }

            return WC()->add_error(__($message, 'soisy'));
        }

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

                    $order->add_order_note($this->success_message);
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

        public function validate_fields()
        {
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

        private function getShopId(): string
        {
            return $this->settings['sandbox_mode'] ? 'soisytests' : $this->settings['shop_id'];
        }
    }
}

function add_soisy_gateway($methods)
{
    $methods[] = 'SoisyGateway';

    return $methods;
}

function load_soisy_translations()
{
    load_plugin_textdomain('soisy', false, basename(dirname(__FILE__)) . '/languages/');
}

function init_soisy_widget_for_cart_and_product_page()
{
    if (is_product() || is_cart()) {
        new SoisyGateway();
    }
}

add_filter('woocommerce_payment_gateways', 'add_soisy_gateway');
add_action('plugins_loaded', 'load_soisy_translations');
add_action('plugins_loaded', 'init_soisy');
add_action('the_post', 'init_soisy_widget_for_cart_and_product_page');
