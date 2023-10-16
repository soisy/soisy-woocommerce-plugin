<?php
	
	class Soisy_Pagamento_Rateale_Public {
		/**
		 * The ID of this plugin.
		 *
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;
		
		/**
		 * The version of this plugin.
		 *
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;
		
		/**
		 * Object that will be passed to public JS
		 *
		 * @access  private
		 * @var     array   $jsVars     plugin vars that will be passed to JS
		 */
		private $jsVars;
		
		/**
		 * Whether to print or initialize widget
		 *
		 * @access  private
		 * @var bool $parseWidget wheter or not print the widget container
		 */
		private $parseWidget;
		
		
		/**
		 * Initialize the class and set its properties.
		 *
		 * @param      string    $plugin_name       The name of the plugin.
		 * @param      string    $version    The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {
			
			$this->plugin_name = $plugin_name;
			$this->version = $version;
			$this->parseWidget = true;
			
		}
		
		/**
		 * Public filter for changing the number of instalments
		 *
		 * @param $amount product price / cart amount
		 *
		 * @return int
		 */
		public function instalments ( $amount = 0 ) {
			
			$opt  = settings_soisy_pagamento_rateale();
			//do_action( 'qm/debug', $opt );
			return apply_filters( 'soisy_instalments', $opt['quote_instalments_amount'], $amount, $this->zeroInterest( $amount ) );
		}
		
		public function shortcodes() {
			add_shortcode( 'soisy-product-widget', [ $this, 'sc_widget' ] );
		}
		
		/**
		 * Public filter for enabling/disabling 0% financing
		 *
		 * @param $amount product price / cart amount
		 *
		 * @return int
		 */
		public function zeroInterest( $amount = 0 ) {
			$opt  = settings_soisy_pagamento_rateale();
			$zero = isset( $opt['soisy_zero'] ) ? $opt['soisy_zero'] : 0;
			return apply_filters( 'soisy_zero', $zero, $amount );
		}
		
		/*
		 PLUGIN HOOKS
		 */
		
		/**
		 * Programmatically appends the widget to the product page according to plugin settings
		 *
		 * @return void
		 */
		public function product_hooks() {
			$this->soisy_available();
			if ( isSoisyAvailable() ) {
				$opt = settings_soisy_pagamento_rateale();
				$hook = empty( $opt['position'] ) ? 'woocommerce_single_product_summary' : $opt['position'];
				
				$product = $this->get_product();
				
				//TODO: save this filter to new versions
				$amount = apply_filters( 'soisy_amount_filter', $product->get_price(), $product );
				
				add_filter( 'init_soisy_w', function ( $args ) use ( $amount ) {
					$args = wp_parse_args( $args, [
							'amount'                   => $amount,
							'soisy_zero'               => $this->zeroInterest( $amount ),
							'quote_instalments_amount' => $this->instalments( $amount )
						]
					);
					
					//do_action( 'qm/debug', $args );
					
					return $args;
				}, 20 );
				
				switch ( $hook ) {
					case 'legacy':
						ob_start();
						$this->render_widget( );
						$widget = ob_get_contents ();
						ob_end_clean ();
						add_filter( 'woocommerce_get_price_html', function ( $price ) use ( $widget ) {
							if ( is_product() && ! is_null( $price ) ) {
								return $price . $widget;
							}
							
							return $price;
						} );
						break;
					default:
						add_action( $hook, [ $this, 'render_widget' ], 15 );
				}
			}
		}
		
		public function sc_widget($args) {
			$args = wp_parse_args( $args, [] );
			ob_start();
			do_action( 'soisy-product-widget' );
			$widget = ob_get_contents ();
			ob_end_clean ();
			ob_flush();
			
			return $widget;
		}
		
		/**
		 * Appends the widget to the cart page
		 *
		 * @return void
		 */
		public function checkout_hooks() {
			add_filter( 'soisy_available', function ( $bool )  {
				if ( !$this->check_range( WC()->cart->get_subtotal() ) ) {
					$bool = false;
				};
				
				return $bool;
			} );
			
			foreach (WC()->cart->get_cart() as $cart_item) {
				$product_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];
				//do_action( 'qm/debug', [$cart_item] );
				$this->soisy_available( $product_id );
			}
			
			if ( $this->parseWidget ) {
				$this->render_widget();
			}
		}
		
		
		public function order_review_hooks() {
			$this->parseWidget = false;
			$this->checkout_hooks();
			$checkout = WC_Checkout::instance();
			if ( 'IT' != $checkout->get_value( 'billing_country' ) ) {
				
				add_filter( 'soisy_available', '__return_false' );
			}
			
			$this->parseWidget = true;
		}
		
		/**
		 * Update the widget values after any change to cart
		 *
		 * @return void
		 */
		public function updated_cart(  ) {
			$this->parseWidget = false;
			$this->checkout_hooks( false );
			printf( '<div id="updatedAmount" data-amount="%s" data-available="%s" style="display:none;"></div>',
				WC()->cart->get_subtotal(),
				intVal( isSoisyAvailable() )
			);
			$this->parseWidget = true;
		}
		
		/*
		 WIDGET FUNCTIONS
		 */
		
		/**
		 * Inizialize the settings used by the widget
		 *
		 * @return void
		 */
		public function init_soisy_widget() {
			
			$opt         = settings_soisy_pagamento_rateale();
			
			$public_keys = [
				'min_amount',
				'max_amount',
				'shop_id',
				'widget_id',
				'thousand_sep',
				'decimal_sep'
			];
			foreach ( $public_keys as $public_key ) {
				$this->jsVars[ $public_key ] = $opt[ $public_key ];
			}
			add_filter( 'init_soisy_w', function ( $args ) {
				$args = wp_parse_args( $args, $this->jsVars );
				
				return $args;
			} );
			
			
			add_filter( 'init_soisy_w', function ( $args ) {
				if ( !is_product() ) {
					$amount = WC()->cart->get_subtotal();
					$args   = wp_parse_args( $args, [
							'amount'                   => $amount,
							'soisy_zero'               => $this->zeroInterest( $amount ),
							'quote_instalments_amount' => $this->instalments( $amount )
						]
					);
				}
				
				return $args;
			}, PHP_INT_MIN );
		}
		
		/**
		 * Prints the HTML container for JS api calls
		 *
		 * @return void
		 */
		public function render_widget( $static = false ) {
			$opt = settings_soisy_pagamento_rateale();
			$IDel = $opt['widget_id'];
			$content = '';
			if ( $static ) {
				$IDel .= '-checkout';
				$amount = floatVal( WC()->cart->total );
				$args = [
					'shop-id'            => $opt['shop_id'],
					'amount'             => $amount,
					'instalments'        => $this->instalments($amount),
					'zero-interest-rate' => $this->zeroInterest( $amount )
				];
				
				foreach ( $args as $attr => $val ) {
					$arHtml[] = sprintf( '%s="%s"', $attr, $val );
				}
				$content = sprintf('<soisy-loan-quote %s></soisy-loan-quote>',implode( ' ', $arHtml ));
				
			}
			printf( '<div id="%s">%s</div>',
				$IDel,
				$content
			);
			
		}
		
		
		
		
		/*
		  CHECK GATEWAY AVAILABILITY
		 */
		
		/**
		 * Check if either the product price as well the cart amount is within plugin settings
		 *
		 * @param $price double price
		 *
		 * @return bool
		 */
		public function check_range( $price ) {
			$opt = settings_soisy_pagamento_rateale();
			$min = $opt['min_amount'];
			$max = $opt['max_amount'];
			if ( $min <= $price && $price <= $max ) {
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Disable plugin if either single products or any inside the cart belongs to excluded categories
		 *
		 * @param $product object single product
		 *
		 * @return bool
		 */
		public function check_product_category( $product ) {
			if ( ! empty( $product ) ) {
				$opt    = settings_soisy_pagamento_rateale();
				$ex_cat = explode( ',', $opt['excluded_cat'] );
				$id = $product->get_type() == 'variation' ? $product->get_parent_id() : $product->get_id();
				$terms  = get_the_terms( $id, 'product_cat' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						$isMain = empty( $term->parent ) || false;
						$idTerm = $isMain ? $term->term_id : $term->parent;
						if ( in_array( $idTerm, $ex_cat ) ) {
							
							return false;
						}
					}
				}
			}
			
			return true;
		}
		
		/**
		 * Adds all the filters to check if widget is available either on single product or on the checkout page
		 *
		 * @param $product_id int/bool single product ID or empty
		 *
		 * @return void
		 */
		public function soisy_available( $product_id = false ) {
			$opt = settings_soisy_pagamento_rateale();
			if ( empty( $opt['enabled'] ) || 'no' == $opt['enabled'] ) {
				add_filter( 'soisy_available', '__return_false', 99, 1 );
			}
			if ( $product = $this->get_product( $product_id ) ) {
				add_filter( 'woocommerce_get_price_html', [
					$this,
					'get_product_price'
				], 10, 2 );
				add_filter( 'soisy_available', function ( $bool ) use ( $product ) {
					if ( empty( $this->check_product_category( $product ) ) ) {
						$bool = false;
					}
					
					return $bool;
				}, 10, 2 );
			}
		}
		
		
		/*
		 HELPERS
		 */
		
		/**
		 * Retrieves plugin settings
		 *
		 * @param $vars
		 *
		 * @return mixed
		 */
		public function get_options( $vars ) {
			$opt = get_option( $this->plugin_name );
			if ( is_array( $opt ) ) {
				$default_check_fields = [
					'shop_id',
					'api_key'
				];
				foreach ( $opt as $k => $v ) {
					if ( ! in_array( $k, $default_check_fields ) || ( in_array( $k, $default_check_fields ) ) && ! empty( $v ) ) {
						$vars[ $k ] = $v;
					}
				}
			}
			
			return $vars;
		}
		
		
		public function get_billing_country() {
			$country = WC()->customer->get_billing_country();
			if ( ! empty( $country ) && $country != 'IT' ) {
				add_filter( 'soisy_available', '__return_false' );
			}
		}
		
		
		public function get_product_price( $price, $product ) {
			add_filter( 'soisy_available', function ( $bool ) use ( $product ) {
				if ( !$this->check_range( $product->get_price() ) ) {
					$bool = false;
				};
				
				return $bool;
			} );
			
			return $price;
		}
		
		
		private function get_product( $product_id = false ) {
			if ( empty( $product_id ) ) {
				$obj = get_queried_object();
				if ( is_object( $obj ) && 'product' == $obj->post_type ) {
					
					$product_id = $obj->ID;
				}
			}
			
			$product = wc_get_product( $product_id );
			
			return is_object( $product ) ? $product : false;
		}
		
		
		/*
		CUSTOM SCRIPTS AND STYLE
		*/
		
		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 */
		public function enqueue_styles() {
			
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/soisy-pagamento-rateale-public.css', [], $this->version, 'all' );
			
		}
		
		/**
		 * Register the JavaScript for the public-facing side of the site.
		 *
		 */
		public function enqueue_scripts() {
			$opt = settings_soisy_pagamento_rateale();
			$api_handle = 'soisy_api_js';
			wp_register_script( $api_handle, $opt['load_quote_cds_js'], [], $this->version, true );
			wp_register_script( $this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-public.js',
				[ 'soisy_api_js' ],
				$this->version,
				true
			);
			
			$jsVars = apply_filters( 'init_soisy_w', [] );
			wp_localize_script( $this->plugin_name,
				'soisyVars',
				$jsVars,
			);
			
			wp_enqueue_script( $api_handle );
			wp_enqueue_script( $this->plugin_name );
		}
		
		/*
		 AJAX ACTIONS
		 */
		
		/**
		 * Listen to remote requests for order updates
		 * @return void
		 */
		public function parseRemoteRequest () {
			$post = $_POST;
			$debug = false;
			if ( false && isset( $_GET['debug'] ) && $_GET['debug'] == 1 ) {
				$post = $_GET;
				$debug = true;
			}
			$controls = [
				'eventId',
				'eventMessage',
				'eventDate',
				'orderToken',
				'orderReference'
			];
			foreach ( $controls as $control ) {
				if ( !isset( $post[$control] ) || empty( $post[$control] ) ) {
					echo json_encode( [ 'request' => 'failed' ] );
					exit();
				}
			}
			
			
			$order = wc_get_order( intVal( $post['orderReference'] ) );
			if ( !is_wp_error( $order ) && !empty( $order ) ) {
				$id = $order->get_id();
				$myToken = get_post_meta( $order->get_id(), 'soisy_orderToken', true );
				if ( $myToken == $post['orderToken'] ) {
					switch ( $post['eventId'] ) {
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
							$amount = $post['amount'] / 100;
							$subject="La pratica Soisy {$id} è stata pagata.";
							$body = "<p>Soisy ti informa che lo stato dell'ordine {$id} p variato in: <strong>RICHIESTA PAGATA</strong></p>";
							$order->add_order_note( "[Soisy] Richiesta di finanziamento pagata" );
							/* add_action( 'woocommerce_order_status_processing', function ( $idOrder ) {
								 wc_reduce_stock_levels( $idOrder );
							 } );*/
							$order->update_status( 'processing' );
							
							break;
						case 'UserWasRejected':
							if ( $post['eventMessage'] == 'payment failed' ) {
								// payment rejected by system
								$order->add_order_note( "Il pagamento è stato rifiutato dal nostro sistema." );
							}
							if ( $post['eventMessage'] == 'documents check KO' ) {
								// document check failed
								$order->add_order_note( "Richiesta fallita. Documentazione non approvata." );
							}
							$order->update_status('failed');
							break;
					}
				}
			}
			if ( false && $debug ) {
				print_r( $order );
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
	}
