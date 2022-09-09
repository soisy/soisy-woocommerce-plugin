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
			
			public $log;
			
			public function __construct()
			{
				$this->id           = 'soisy';
				$this->icon         = apply_filters('woocommerce_Soisy_icon',  plugin_dir_url(__FILE__) . '/assets/images/logo-soisy-min.png');
				
				$this->supports    = ['soisy_payment_form'];
				$this->has_fields  = true;
				$this->form_fields = [];
			
				$this->init_settings();
				
				foreach ( soisyVars() as $setting => $value ) {
					if ( ! isset( $this->settings[ $setting ] ) ) {
						$this->settings[ $setting ] = $value;
					}
					if ( ! isset( $this->settings['reset_zero'] ) ) {
						$this->settings['soisy_zero'] = 0;
					}
				}
    
				add_filter( 'soisy_vars', function ( $var ) {
					if ( is_array( $this->settings ) ) {
						foreach ( $this->settings as $key => $val ) {
							$var[ $key ] = $val;
						}
						$var['shop_id'] = $this->settings['sandbox_mode'] ? 'soisytests' : $this->settings['shop_id'];
					}
					
					return $var;
				} );
				
				$this->init_form_fields();
				$this->log = boolval( $this->settings['logger'] );
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
				
				add_filter('woocommerce_available_payment_gateways', [&$this, 'payment_gateway_disable_countries'], 99);
				add_filter('woocommerce_available_payment_gateways', [&$this, 'payment_gateway_disable_by_amount'], 99);
				
				
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
				if (is_product() && !is_null($price)) {
					return $price . $this->showLoanQuoteWidgetForProduct($price);
				}
				
				return $price;
			}
			
			public function add_soisy_cart_page()
			{
				// Sorry if you're reading the following condition, but WooCommerce sucks so bad...
				
				if (!empty($_SESSION['soisy-loan-quote-widget-called'])) {
					return;
				}
				
				$_SESSION['soisy-loan-quote-widget-called'] = true;
				
				echo $this->showLoanQuoteWidgetForCartAndCheckout();
			}
			
			function make_script_async( $tag, $handle, $src )
			{
				return $tag;
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
			
			public function payment_gateway_disable_by_amount($available_gateways) {
				$currentTotal = 0;
				$this->logger( [ 'caller' => __FUNCTION__ ] );
				if ( is_object( WC()->cart ) ) {
					$cart = WC()->cart;
					if ( isset( $cart->total ) && !empty( $cart->total ) ) {
						$currentTotal = $cart->total;
					}
					else {
						$currentTotal = Helper::htmlPriceToNumber( $cart->get_total() );
					}
					$this->logger( ['cart_total' => $currentTotal] );
                    if ( isset( $available_gateways['soisy'] ) && !empty($currentTotal) ) {
						if ( $currentTotal < $this->settings['min_amount'] || $currentTotal > $this->settings['max_amount'] ) {
							$this->logger( [
                                    'unsetting due to:',
                                    'cart_total' => $currentTotal,
                                    'min' =>$this->settings['min_amount'],
                                    'max' =>$this->settings['max_amount']
                            ] );
                            unset( $available_gateways['soisy'] );
							add_filter( 'check_soisy_usable', function ( $str ) {
								$str = 'invalid';
								
								return $str;
							} );
						}
						else {
							$this->logger( 'soisy is an available gateway' );
						}
					}
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
			
			public function forceInstalments () {
				return apply_filters( 'soisy_force_instalments', false );
			}
			
			public function instalments ( $amount = 0, $zeroRate = false ) {
				return apply_filters( 'soisy_instalments', $this->settings['quote_instalments_amount'], $amount,
					$zeroRate );
			}
			
			public function forceTaxCalculation () {
				return apply_filters( 'soisy_force_taxes', false );
			}
			
			public function tax_this_price( $product, $price ) {
				if ( $this->forceTaxCalculation() && is_numeric ( $price ) ) {
					$tax_rates  = WC_Tax::get_rates ( $product->get_tax_class () );
					$taxes      = WC_Tax::calc_tax ( $price, $tax_rates, false );
					$tax_amount = WC_Tax::get_tax_total ( $taxes );
					//return round ( $price + $tax_amount, wc_get_price_decimals () );
					return $price + $tax_amount;//round ( $price + $tax_amount, 0 );
				}
				
				return 0;
			}
			
			public function zeroInterest ($amount = 0) {
				//$soisy = get_option( 'woocommerce_soisy_settings' );
				$zero = isset( $this->settings['soisy_zero'] ) ? $this->settings['soisy_zero'] : false;
				
				return apply_filters( 'soisy_zero', $zero, $amount );
			}
			
			public function showWidgetInTaxonomies () {
				$bool = true;
				if ( is_archive() ) {
					$bool = false;
				}
    
				return apply_filters( 'soisy_show_in_taxonomies', $bool );
			}
			
			public function check_soisy ( $page ) {
    
				return apply_filters( 'check_soisy_usable', true, $page );
			}
			
			public function process_payment($order_id)
			{
				$this->client = new SoisyClient(
					$this->settings['shop_id'],
					$this->settings['api_key'],
					$this->settings['sandbox_mode']
				);
				
				$order = new WC_Order($order_id);
				
				$amount = $order->get_total() * 100;
				
				$email = sanitize_email( $order->get_billing_email() );
				$params = [
					'firstname'      => sanitize_text_field( $order->get_billing_first_name() ),
					'lastname'       => sanitize_text_field( $order->get_billing_last_name() ),
					'email'          => $email,
					'mobilePhone'    => sanitize_text_field( $_POST['billing_phone'] ),
					'amount'         => $amount,
					'orderReference' => $order_id,
					'callbackUrl'    => plugin_dir_url( __FILE__ ) . 'soisy-listener.php?action=order_status'
				];
				
				$zero = $this->zeroInterest($amount);
				
				if ( $this->forceInstalments() ) {
					$instalments = $this->instalments( $amount/100, $zero );
					if ( $instalments > 0 ) {
						$params['instalments'] = $instalments;
					}
				}
				
				if ( $zero ) {
					$params['zeroInterestRate'] = $zero;
				}
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
					
					//save order token
					update_post_meta( $order->get_id(), 'soisy_orderToken', $orderToken );
					
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
				$shop_id = $this->settings['sandbox_mode'] ? 'soisytests' : $this->settings['shop_id'];
				/*add_filter( 'soisy_vars', function ( $vars ) use ($shop_id) {
					$vars['shop_id'] = $shop_id;
					
					return $vars;
				} );*/
    
				return $shop_id;
			}
			
			public function showLoanQuoteWidgetForProduct($price): string
			{
				if ( false == $this->showWidgetInTaxonomies() ) {
					return '';
				}
				global $woocommerce_loop;
				if ( is_product() && $woocommerce_loop['name'] == 'related' ) {
					return '';
				}
				if (is_null($price)) {
					return '';
				}
				
				if (Helper::isSoisyLoanQuoteCalculatedAlready($price)) {
					return '';
				}
				
				return $this->renderLoanQuoteWidget($price);
			}
			
			public function showLoanQuoteWidgetForCartAndCheckout(): string {
				
				/*if ( false == $this->showWidgetInTaxonomies() ) {
					return '';
				}*/
				if ( is_object( WC()->cart ) && ! empty( ( WC()->cart->get_total() ) ) ) {
					$cart = WC()->cart;
					if ( isset( $cart->total ) && !empty( $cart->total ) ) {
						$currentTotal = $cart->total;
					}
					else {
						$currentTotal = Helper::htmlPriceToNumber( $cart->get_total() );
					}
     
					return $this->renderLoanQuoteWidget( $currentTotal, true );
				} else {
					return '';
				}
    
				
				
			}
			
			public function renderLoanQuoteWidget($price, $isCheckout=false): string {
				$legacy = true;
				$shop_id = $this->getShopIdForLoanQuote();
				if ( is_product() ) {
					global $product;
					if ( is_object( $product ) ) {
						$type = $product->get_type();
						switch ( true ) {
                            case (!empty($product->get_children())):
							case $type == 'variable':
							    return '';
								break;
							default:
								$legacy = false;
								$price = $product->get_display_price();
						}
					}
				}
				if ( $isCheckout ) {
					$this->logger( [
						'Rendering Widget for checkout',
						'price' => $price
					] );
					$legacy = false;
				}
				
				if( true === $legacy ){
					$price = Helper::htmlPriceToNumber( $price );
				}
				
				$res = '';
				if (!Helper::isCorrectAmount($price)) {
					return $res;
				}
				
				
				$page = null;
				$page = is_product() ? 'product' : $page;
				$page = is_checkout() ? 'checkout' : $page;
				$zero = $this->zeroInterest( $price );
				$instalments = $this->instalments( $price, $zero );
				$check = $this->check_soisy( $page );
				
				if ( $check == 'invalid' ) {
					$res = sprintf( '<script>document.getElementById("payment_method_soisy").disabled=true; </script>' );
				}
				if ( $check == 'valid' ) {
					$res = sprintf( '<soisy-loan-quote shop-id="%s" amount="%s" instalments="%s" zero-interest-rate="%s"></soisy-loan-quote>',
						$shop_id,
						$price,
						$instalments,
						$zero
					);
				}
				
				ob_start();
				print $res;
				return ob_get_clean();
			}
			
			public function parseRemoteRequest () {
				$controls = [
					'eventId',
					'eventMessage',
					'eventDate',
					'orderToken',
					'orderReference'
				];
				foreach ( $controls as $control ) {
					if ( !isset( $_POST[$control] ) || empty( $_POST[$control] ) ) {
						echo json_encode( [ 'request' => 'failed' ] );
						exit();
					}
				}
				
				
				$order = wc_get_order( intVal( $_POST['orderReference'] ) );
				if ( !is_wp_error( $order ) && !empty( $order ) ) {
					$id = $order->get_id();
					$myToken = get_post_meta( $order->get_id(), 'soisy_orderToken', true );
					if ( $myToken == $_POST['orderToken'] ) {
						switch ( $_POST['eventId'] ) {
							case 'LoanWasApproved':
								//loan approved, happy merchant
								$order->add_order_note( '[Soisy] Richiesta di finanziamento in corso' );
								break;
							case 'RequestCompleted':
								//request completed, more than happy merchant
								$order->add_order_note( '[Soisy] Richiesta di finanziamento in attesa di verifica' );
								break;
							case 'LoanWasVerified':
								//waiting for disbursment, we will pay the merchant soon
								$subject="La pratica Soisy {$id} è stata arrovata.";
								$body = "<p>Soisy ti informa che lo stato dell'ordine {$id} p variato in: <strong>RICHIESTA APPROVATA</strong></p>";
								$order->add_order_note( "[Soisy] Richiesta di finanziamento approvata" );
								break;
							case 'LoanWasDisbursed':
								//money, money money!
								$amount = $_POST['amount'] / 100;
								$subject="La pratica Soisy {$id} è stata pagata.";
								$body = "<p>Soisy ti informa che lo stato dell'ordine {$id} p variato in: <strong>RICHIESTA PAGATA</strong></p>";
								$order->add_order_note( "[Soisy] Richiesta di finanziamento pagata" );
								/* add_action( 'woocommerce_order_status_processing', function ( $idOrder ) {
									 wc_reduce_stock_levels( $idOrder );
								 } );*/
								$order->update_status( 'processing' );
								
								break;
							case 'UserWasRejected':
								if ( $_POST['eventMessage'] == 'payment failed' ) {
									// payment rejected by system
									$order->add_order_note( "Il pagamento è stato rifiutato dal nostro sistema." );
								}
								if ( $_POST['eventMessage'] == 'documents check KO' ) {
									// document check failed
									$order->add_order_note( "Richiesta fallita. Documentazione non approvata." );
								}
								$order->update_status('failed');
								break;
						}
					}
				}
				
				$urlparts = parse_url(home_url());
				$domain = $urlparts['host'];
				$recipients = [
					get_bloginfo('name') => get_bloginfo( 'admin_email' )
				];
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$headers[] = "From: Plugin Soisy <noreply@$domain>";
				
				if ( false && !empty( $subject ) ) {
					foreach ( $recipients as $name => $recipient ) {
						wp_mail( $recipient, $subject, $body, $headers );
						
					}
				}
				
				echo json_encode( [ 'request' => 'ok' ] );
				
			}
			
			
			private function logger ( $mixMsg, $strAction = 'append' ) {
				if ( false == $this->log ) {
					return;
				}
				$file = sprintf( '%ssoisy_logger.txt',
					 wp_get_upload_dir()['path']
				);
				
				switch ( $strAction ) {
					case 'read':
						$mode = 'r';
						break;
					case 'reset':
						$mode = 'w';
						break;
					default:
						$mode = 'a';
						break;
				}
				$handle = fopen( $file, $mode );
				switch ( true ) {
					case null == $mixMsg:
						$res = fread( $handle, filesize( $file ) );
						break;
					case is_array( $mixMsg ):
						$row = sprintf( "\n[%s] passed array:\n%s\n%s",
							date( 'Y-m-d H:i:s' ),
							print_r( $mixMsg, true ),
							str_repeat( '#', 15 )
						);
						$res = fwrite( $handle, $row );
						break;
					default:
						$row = sprintf( "\n[%s] %s",
							date( 'Y-m-d H:i:s' )
							, $mixMsg
						);
						$res = fwrite( $handle, $row );
						break;
				}
				
				return $res;
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
		if ( is_admin() ) {
            return;
		}
		if (is_product() || is_cart() || is_checkout()) {
			
			/*add_filter( 'soisy_vars', function ( $vars ) {
				$soisy = get_option( 'woocommerce_soisy_settings' );
				foreach ( $vars as $setting => $ignore ) {
					if ( !empty( $soisy[$setting] ) ) {
						$vars[$setting] = $soisy[$setting];
					}
				}
				return $vars;
			} );*/
			new SoisyGateway();
			/*add_action( 'wp_print_scripts', function () {*/
				$g = [
					'quote_instalments_amount',
					'min_amount',
					'max_amount',
					'soisy_zero',
                    'shop_id'
				];
			
    
				foreach ( soisyVars() as $k => $v ) {
					//foreach ( apply_filters('soisy_vars',[]) as $k => $v ) {
					if ( in_array( $k, $g ) ) {
						$vars[$k] = $v;
					}
				};
				
				wp_enqueue_script( 'soisy-public', plugin_dir_url( __FILE__ ) . 'assets/soisy_public.js', [], time(), true );
				
				wp_localize_script( 'soisy-public',
					'soisypublic',
					$vars
				);
			/*} );*/
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
	//add_action('init', 'init_soisy_widget_for_cart_and_product_page');
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_soisy_action_links');
	
	add_action( 'admin_enqueue_scripts', function () {
		if ( is_object( get_current_screen() ) &&get_current_screen()->base=='woocommerce_page_wc-settings' ) {
			$admin_vars['haystacks']['allCats'] = soisyAdminCategorySearch();
			wp_enqueue_script( 'soisy-admin', plugin_dir_url( __FILE__ ) . 'assets/soisy-admin.js', array( 'jquery', 'select2' ), time(), true );
			
			wp_localize_script( 'soisy-admin',
				'adminVars',
				$admin_vars
			);
		}
	} );
 
	add_action( 'soisy_ajax_order_status', function () {
		$soisy = new SoisyGateway();
		$soisy->parseRemoteRequest();
	} );
	
	add_filter( 'check_soisy_usable', function ( $result, $page ) {
		$vars = soisyVars();
		$forbidden = explode( ',', $vars['excluded_cat'] );
		switch ( $page ) {
            case 'product':
                global $product;
	            if ( is_object( $product ) ) {
                    if ( ! empty( array_intersect( $forbidden, $product->get_category_ids() ) ) ) {
                        $result = 'unavailable';
                    }
		    }
                break;
            default:
	            if ( is_object( WC()->cart ) ) {
		            $cart = WC()->cart->get_cart();
		            foreach ( $cart as $cart_item ) {
			            $product = wc_get_product( $cart_item['product_id'] );
			            if ( is_object($product) &&
                             ! empty( array_intersect( $forbidden, $product->get_category_ids()  ) )
                        ) {
				            $result = 'unavailable';
			            }
		            }
	            }
                break;
		}
		
		return $result;
	}, 10, 2 );
    
    function soisyAdminCategorySearch( $res = []) {
	    $args = array (
		    'taxonomy'     => [ 'product_cat' ],
		    'hierarchical' => true,
		    'order'        => 'ASC',
		    'orderby'      => 'name',
		    'fields'       => 'all',
		    'hide_empty'   => true,
		    'get'          => 'all',
	    );

// The Term Query
	    $term_query = new WP_Term_Query( $args );
	    $admin_products = [];
	    $filtered = [];
	    //do_action('qm/debug', $term_query);
	    if ( is_array( $term_query->terms ) ) {
		    foreach ( $term_query->terms as $term ) {
			    $wholeTerms[ $term->term_id ] = $term->name;
			    if ( $term->parent == 0 ) {
				    $orphans[ $term->term_id ] = $term->name;
				    $legacy[ $term->term_id ]  = [ $term->term_id ];
			    } else {
				    $legacy[ $term->term_id ][]                    = $term->parent;
				    $legacy[ $term->term_id ][]                    = $term->term_id;
				    $descendant[ $term->parent ][ $term->term_id ] = $term->name;
			    }
		    }
		    foreach ( $legacy as $id => $ar ) {
			    $label = [];
			    foreach ( $ar as $item ) {
				    $label[] = $wholeTerms[ $item ];
			    }
			    $labels[ $id ] = $label;
		    }
		
		    foreach ( $term_query->terms as $term ) {
			    $id = $term->term_id;
			    if ( $term->parent > 0 ) {
				    $merge                  = array_unique( array_merge( $labels[ $term->parent ], $labels[ $id ] ) );
				    $unsort_products[ $id ] = [
					    'id'   => $id,
					    'text' => implode( ' / ', $merge )
				    ];
			    } else {
				    $unsort_products[ $id ] = [
					    'id'   => $id,
					    'text' => implode( ' / ', $labels[ $id ] )
				    ];
			    }
			    $sort[ $id ] = $unsort_products[ $id ]['text'];
		    }
		    asort( $sort );
		
		    foreach ( $sort as $id => $void ) {
			    $admin_products[] = $unsort_products[ $id ];
			    if ( in_array( $id, $res ) ) {
				    $filtered[ $id ] = $void;
			    }
		    }
		
	    }
	    if ( ! empty( $res ) ) {
		    return $filtered;
	    }
	
	    return $admin_products;
    }
	
	function soisyVars () {
		$vars = [
			'quote_instalments_amount' => 12,
			'min_amount'               => 100,
			'max_amount'               => 15000,
			'soisy_zero'               => 0,
			'logger'                   => 0
		];
		
		return apply_filters( 'soisy_vars', $vars );
	}
