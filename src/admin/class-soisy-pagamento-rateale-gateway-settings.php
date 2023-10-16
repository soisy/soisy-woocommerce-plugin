<?php
function init_Soisy_Pagamento_Rateale_Gateway_Settings()
{

    class Soisy_Pagamento_Rateale_Gateway_Settings extends WC_Payment_Gateway
    {
        protected $plugin_vars;
        protected $td;

        public function __construct() {
	        $this->id         = 'soisy';
	        $this->icon       = '';
	        $this->has_fields = true;
		
	        $this->title              = __( 'Paga a rate con Soisy', $this->td );
	        $this->method_title       = __( 'Soisy Pagamento Rateale', $this->td );
	        $this->method_description = __( 'Allow your customers to pay in instalments with Soisy, the P2P lending payment method', $this->td );
	        $this->success_message    = __( 'Thanks for choosing Soisy', $this->td );
	        $this->supports           = [ 'soisy_payment_form' ];
	        $this->plugin_vars        = settings_soisy_pagamento_rateale();
	        $this->td                 = $this->plugin_vars['textdomain'];
	        $this->form_fields        = $this->init_form_fields();
	        $this->init_settings();
	        $this->update_options();
	        $this->hooks();
        }
	
	
	    public function enqueue_gateway( $gateways ) {
		    if ( empty( isSoisyAvailable() ) && isset( $gateways [$this->id]) ) {
			    unset ( $gateways[$this->id] );
		    }

		    return $gateways;
	    }
	
	    protected function update_options () {
			update_option( 'soisy-pagamento-rateale', $this->settings );
	    }
	
	    public function hooks() {
		    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [
			    &$this,
			    'process_admin_options'
		    ] );
		
		    add_filter( 'woocommerce_available_payment_gateways', [
			    $this,
			    'enqueue_gateway'
		    ] );
		
		    add_filter( 'soisy_agree_tos', [
			    $this,
			    'checkout_form_tos'
		    ] );
		    add_filter( 'soisy_additional_values', [
			    $this,
			    'checkout_additional_values'
		    ] );
		    add_filter( 'woocommerce_can_reduce_order_stock', [
			    $this,
			    'soisy_do_not_reduce_stock'
		    ], 10, 2 );
	    }

	    public function forceInstalments () {
		
		    return apply_filters( 'soisy_force_instalments', false );
	    }
	
	    public function process_payment( $order_id ) {
		    $options = settings_soisy_pagamento_rateale();
		    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/SoisyApiConnection.php';
		
		    $this->client = new SoisyApiConnection( $options );
		
		    $order = new WC_Order( $order_id );
		
		    $amount = $order->get_total() * 100;
		    $params = apply_filters( 'init_soisy_w', [] );
		    $params = wp_parse_args( $params, [
			    'firstname'      => sanitize_text_field( $order->get_billing_first_name() ),
			    'lastname'       => sanitize_text_field( $order->get_billing_last_name() ),
			    'email'          => sanitize_email( $order->get_billing_email() ),
			    'mobilePhone'    => sanitize_text_field( $_POST['billing_phone'] ),
			    'orderReference' => $order_id,
			    'callbackUrl'    => $options['ajax_listener']
		    ] );
		    $params['amount'] = $amount;
		    if ( empty( $this->forceInstalments() ) ) {
			    unset( $params['instalments'] );
		    }
		    if ( isset( $params['soisy_zero'] ) && $params['soisy_zero'] > 0 ) {
			    $params['zeroInterestRate'] = 1;
		    }
		    unset( $params['soisy_zero'] );
		
		   
		    try {
			    $orderToken = $this->client->createSoisyOrder( $params );
			
			    if ( is_null( $orderToken ) ) {
				    throw new \Error( 'Order token is null. Request failed.' );
			    }
			
			    WC()->session->set( 'soisy_token', $orderToken );
			
			
			    if ( $order->status !== 'completed' ) {
				    $order->update_status( 'on-hold' );
				    WC()->cart->empty_cart();
				
				    $order->add_order_note( $this->success_message );
				    unset( $_SESSION['order_awaiting_payment'] );
			    }
			
			    //save order token
			    update_post_meta( $order->get_id(), 'soisy_orderToken', $orderToken );
			
			    return [
				    'result'   => 'success',
				    'redirect' => $this->client->getRedirectUrl( WC()->session->get( 'soisy_token' ) ),
			    ];
		    } catch ( \DomainException $e ) {
			    $errorMessage = sprintf( "%s: %s", __( 'Validation error', $this->td ), $e->getMessage() );
			
			    $order->add_order_note( $errorMessage );
			    $order->update_status( 'failed' );
			
			    wc_add_notice( $errorMessage );
		    } catch ( \Error $e ) {
			    $errorMessage = sprintf( "%s: %s", __( 'HTTP request error', $this->td ), $e->getMessage() );
			
			    $order->add_order_note( $errorMessage );
			    $order->update_status( 'failed' );
			
			    wc_add_notice( $errorMessage );
		    }
	    }
		
		
        /**
         * Return admin settings form for Soisy
         *
         * @return array
         */
	    public function init_form_fields() {
		
		    $position_descr = __('<span>You can choose widget position</span><ul><li><strong>Legacy</strong>: Select this option when other positions don\'t display properly.</li><li><strong>Custom</strong>: You can display the widget in your custom templates using the shortcode <i>["soisy-product-widget"]</i></li></ul>', $this->td);
			
		    $fieldset = [
			    'enabled'                  => [
				    'title'   => __( 'Enable', $this->td ),
				    'type'    => 'checkbox',
				    'label'   => __( 'Enable Soisy payment', $this->td ),
				    'default' => 'yes',
			    ],
			    'sandbox_mode'             => [
				    'title'       => __( 'Sandbox mode', $this->td ),
				    'type'        => 'select',
				    'default'     => 1,
				    'class'       => 'wc-enhanced-select',
				    'options'     => [
					    1 => __( 'Yes', 'woocommerce' ),
					    0 => __( 'No', 'woocommerce' ),
				    ],
				    'description' => __( 'If the Sandbox mode is active, no order will be passed to Soisy. Once you ran your test, fill in your merchant data and disable Sandbox mode.', $this->td ),
			    ],
			    'shop_id'                  => [
				    'title'       => __( 'Shop ID', $this->td ),
				    'type'        => 'text',
				    'default'     => 'partnershop',
				    'description' => __( 'Your Soisy Shop ID', $this->td ),
			    ],
			    'api_key'                  => [
				    'title'       => __( 'API key', $this->td ),
				    'type'        => 'text',
				    'default'     => 'partnerkey',
				    'description' => __( 'Your Soisy API key', $this->td ),
			    ],
			    'quote_instalments_amount' => [
				    'title'       => __( 'Instalments', $this->td ),
				    'type'        => 'text',
				    'description' => __( 'Number of Instalments (this applies only to quote widget)', $this->td ),
				    'default'     => $this->plugin_vars['quote_instalments_amount']
			    ],
			    'min_amount'               => [
				    'title'       => __( 'Minimum amount', $this->td ),
				    'type'        => 'text',
				    'description' => __( 'Minimum financing amount', $this->td ),
				    'default'     => $this->plugin_vars['min_amount']
			    ],
			    'max_amount'               => [
				    'title'       => __( 'Maximum amount', $this->td ),
				    'type'        => 'text',
				    'description' => __( 'Maximum financing amount', $this->td ),
				    'default'     => $this->plugin_vars['max_amount']
			    ],
			    'soisy_zero'               => [
				    'title'       => __( 'Interest Free', $this->td ),
				    'type'        => 'select',
				    'default'     => 0,
				    'class'       => 'wc-enhanced-select',
				    'options'     => [
					    1 => __( 'Yes', 'woocommerce' ),
					    0 => __( 'No', 'woocommerce' ),
				    ],
				    'description' => __( 'Enable Zero interest rates. If enabled, your merchant fees will be updated accordingly, as per TOS Agreement', $this->td ),
			    ],
			    /*'logger'                   => [
					'title'       => __( 'Activate debug logger', $this->td  ),
					'type'        => 'select',
					'default'     => 0,
					'class'       => 'wc-enhanced-select',
					'options'     => [
						1 => __( 'Yes', 'woocommerce' ),
						0 => __( 'No', 'woocommerce' ),
					],
					'description' => __( 'Enable The Debug Logger', $this->td  ),
				],*/
			    'show_exclusions'          => [
				    'title'       => __( 'Exclude categories from Financing', $this->td ),
				    'type'        => 'select',
				    'default'     => 0,
				    'class'       => 'select',
				    'options'     => [],
				    'description' => __( 'Select categories to be excluded from financing', $this->td ),
			    ],
			    'position'                 => [
				    'title'       => __( 'Widget position', $this->td ),
				    'type'        => 'select',
				    'default'     => 0,
				    'class'       => 'wc-enhanced-select',
				    'options'     => $this->plugin_vars['widget_position'],
				    'description' => $position_descr,
			    ],
			    'reset_zero'               => [
				    'type'    => 'hidden',
				    'default' => 'yes'
			    ],
			    'excluded_cat'             => [
				    'type'    => 'hidden',
				    'default' => $this->plugin_vars['excluded_cat']
			    ]
		    ];
		
		    return apply_filters( 'soisy_admin_fieldset', $fieldset );
        }
	
	    public function admin_options() {
		    $this->instance_options();
	    }
	
	    public function get_form_data() {
		    return get_option($this->get_option_key() . '_instalment_table', null);
	    }
	
	    public function payment_fields()
	    {
		    if ($this->supports('soisy_payment_form') && is_checkout()) {
			    $this->form();
		    }
	    }
	
	    public function instance_options() {
		    printf( '<table class="form-table">%s</table>', $this->generate_settings_html($this->init_form_fields(), false) );
	    }
	
	    public function form() {
			global $soisyId;
		    $soisyId = $this->id;
		    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/soisy-pagamento-rateale-public-display.php';
	    }
	
	    public function displayErrorMessage($message)
	    {
		    if ($this->getWoocommerceVersionNumber() >= 2.1) {
			    return wc_add_notice(__($message, $this->td), 'error');
		    }
		
		    return WC()->add_error(__($message, $this->td));
	    }
	
	    public function checkout_form_tos( $args ) {
		    $id          = esc_attr( sprintf( '%s-checkbox', $this->id ) );
			
		    $args[ $id ] = [
			    'type'     => 'checkbox',
			    'class'    => [ 'form-row form-row-wide validate-required' ],
			    'label'    => __( 'I Agree submitting the info to Soisy page', $this->td ) . ' ' . "<a target='_blank' href='https://www.soisy.it/privacy-policy/'>" . __( 'Read Soisy Privacy', $this->td ) . "</a>",
			    'required' => true,
		    ];
		
		    return $args;
	    }
	
	    public function checkout_additional_values( $args ) {
		    $vals = [
			    'phone'    => esc_attr( WC()->customer->get_billing_phone() ),
			    'checkbox' => ''
		    ];
		    foreach ( $vals as $raw => $val ) {
			    $key = esc_attr( sprintf( '%s-%s', $this->id, $raw ) );
			    $args[ $key ] = $val;
			}
		
		    return $args;
	    }
	
	
	    public function soisy_do_not_reduce_stock( $reduce_stock, $order ) {
		    if ( $order->has_status( 'on-hold' ) && $order->get_payment_method() == $this->id ) {
			    $reduce_stock = false;
		    }
		
		    return $reduce_stock;
	    }
    }
	
	
}