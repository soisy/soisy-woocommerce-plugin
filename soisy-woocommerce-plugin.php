<?php
/**
 * Plugin Name: Soisy Pagamento Rateale
 * Plugin URI: https://doc.soisy.it/it/Plugin/WooCommerce.html
 * Description: Soisy, la piattaforma di prestiti p2p che offre ai tuoi clienti il pagamento a rate
 * Version: ${VERSION}
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

use Soisy\Includes\Helper;
use Soisy\SoisyClient;
use Soisy\Includes;

define('WC_SOISY_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

require_once(trailingslashit(dirname(__FILE__)) . '/includes/autoloader.php');

function init_soisy()
{
    class SoisyGateway extends WC_Payment_Gateway
    {
        /** @var array $availableCountries */
        protected $availableCountries = ['IT'];

        /** @var SoisyClient $client */
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

            add_filter('woocommerce_get_price_html', [&$this, 'add_soisy_product_page']);
            add_action('woocommerce_proceed_to_checkout', [&$this, 'add_soisy_cart_page']);

            wp_enqueue_script('soisy-loan-quote-widget', SoisyClient::LOAN_QUOTE_CDN_JS, [], null, true);
            add_filter('script_loader_tag', [&$this, 'make_script_async'], 10, 3);

            add_filter( 'woocommerce_can_reduce_order_stock', [&$this, 'soisy_do_not_reduce_stock'], 10, 2 );
        }

        public function soisy_do_not_reduce_stock( $reduce_stock, $order )
        {
            if ( $order->has_status( 'on-hold' ) && $order->get_payment_method() == $this->id ) {
                $reduce_stock = false;
            }

            return $reduce_stock;
        }

        public function add_soisy_product_page($price)
        {
            if (is_product()) {
                return $price . $this->showLoanQuoteWidgetForProduct($price);
            }

            return $price;
        }

        public function add_soisy_cart_page()
        {
            // Sorry if you're the following condition, this but WooCommerce sucks so bad...

            if (!empty($_SESSION['soisy-loan-quote-widget-called'])) {
                return;
            }

            $_SESSION['soisy-loan-quote-widget-called'] = true;

            echo $this->showLoanQuoteWidgetForCartAndCheckout();
        }

        function make_script_async( $tag, $handle, $src )
        {
            if ( 'soisy-loan-quote-widget' != $handle ) {
                return $tag;
            }

            return str_replace(
                "src='".SoisyClient::LOAN_QUOTE_CDN_JS."'>",
                "src='".SoisyClient::LOAN_QUOTE_CDN_JS."' async defer>",
                $tag
            );
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
            $currentTotal = SoisyGateway::get_order_total();

            if (isset($available_gateways['soisy']) && ($currentTotal < SoisyClient::MIN_AMOUNT || $currentTotal > SoisyClient::MAX_AMOUNT)) {
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
                <?=$this->showLoanQuoteWidgetForCartAndCheckout(); ?>
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
            $this->client = new SoisyClient(
                $this->settings['shop_id'],
                $this->settings['api_key'],
                $this->settings['sandbox_mode']
            );

            $order = new WC_Order($order_id);

            $amount = SoisyGateway::get_order_total() * 100;

            $params = [
                'firstname'   => sanitize_text_field($order->get_billing_first_name()),
                'lastname'    => sanitize_text_field($order->get_billing_last_name()),
                'email'       => sanitize_email($order->get_billing_email()),
                'mobilePhone' => sanitize_text_field($_POST['billing_phone']),
                'amount'      => $amount,
            ];

            try {
                $orderToken = $this->client->createSoisyOrder($params);

                if (is_null($orderToken)) {
                    throw new \Error('Order token is null. Request failed.');
                }

                WC()->session->set('soisy_token', $orderToken);

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

        public function getShopIdForLoanQuote(): string
        {
            return $this->settings['sandbox_mode'] ? 'soisytests' : $this->settings['shop_id'];
        }

        public function showLoanQuoteWidgetForProduct($price): string
        {
            if (Helper::isSoisyLoanQuoteCalculatedAlready($price)) {
                return '';
            }

            $price = Helper::htmlPriceToNumber($price);

            return $this->renderLoanQuoteWidget($price);
        }

        public function showLoanQuoteWidgetForCartAndCheckout(): string
        {
            return $this->renderLoanQuoteWidget(SoisyGateway::get_order_total());
        }

        public function renderLoanQuoteWidget($price): string
        {
            if (!Helper::isCorrectAmount($price)) {
                return '';
            }

            ob_start();
            ?>
            <br>
            <soisy-loan-quote
                    shop-id="<?=$this->getShopIdForLoanQuote(); ?>"
                    amount="<?=$price; ?>"
                    instalments="<?=SoisyClient::QUOTE_INSTALMENTS_AMOUNT; ?>"></soisy-loan-quote>
            <br>
            <?php

            return ob_get_clean();
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
    load_plugin_textdomain('soisy', false, basename(dirname(__FILE__)) . '/languages');
}

function init_soisy_widget_for_cart_and_product_page()
{
    if (is_product() || is_cart() || is_checkout()) {
        new SoisyGateway();
    }
}

function add_soisy_action_links($links)
{
    $link = get_admin_url(null, 'admin.php') . '?' . http_build_query([
        'page' => 'wc-settings',
        'tab' => 'checkout',
        'section' => 'soisy',
    ]);

    return array_merge(["<a href='$link'>Settings</a>"], $links);
}

add_filter('woocommerce_payment_gateways', 'add_soisy_gateway');
add_action('plugins_loaded', 'init_soisy');
add_action('plugins_loaded', 'load_soisy_translations');
add_action('the_post', 'init_soisy_widget_for_cart_and_product_page');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_soisy_action_links');