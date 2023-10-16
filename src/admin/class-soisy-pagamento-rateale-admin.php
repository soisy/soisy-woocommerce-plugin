<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Soisy_Pagamento_Rateale
 * @subpackage Soisy_Pagamento_Rateale/admin
 * @author     Mirko Bianco <mirko@acmemk.com>
 */
class Soisy_Pagamento_Rateale_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}
	
	/**
	 * Plugin vars definition
	 *
	 * @return array
	 */
    public function soisy_vars() {
	    return [
		    'quote_instalments_amount' => 12,
		    'min_amount'               => 100,
		    'max_amount'               => 15000,
		    'soisy_zero'               => 0,
		    'logger'                   => 0,
		    'excluded_cat'             => '',
		    'thousand_sep'             => wc_get_price_thousand_separator(),
		    'decimal_sep'              => wc_get_price_decimal_separator(),
		    'widget_id'                => 'soisy-widget-container',
		    'textdomain'               => $this->plugin_name,
		    'shop_id'                  => 'partnershop',
		    'api_key'                  => 'partnerkey',
		    'apiBaseUrl'               => [
			    'sandbox' => 'https://api.sandbox.soisy.it/api/shops',
			    'prod'    => 'https://api.soisy.it/api/shops'
		    ],
		    'webappBaseUrl'            => [
			    'sandbox' => 'https://shop.sandbox.soisy.it',
			    'prod'    => 'https://shop.soisy.it'
		    ],
		    'widget_position'          => [
			    'woocommerce_single_product_summary'    => __( 'Single Product Summary', $this->plugin_name ),
			    'woocommerce_before_add_to_cart_form'   => __( 'Before Add To Cart Form', $this->plugin_name ),
			    'woocommerce_before_add_to_cart_button' => __( 'Before Add To Cart Button', $this->plugin_name ),
			    'woocommerce_after_add_to_cart_button'  => __( 'After Add To Cart Button', $this->plugin_name ),
			    'legacy'                                => __( 'Legacy Plugin Position', $this->plugin_name ),
			    'soisy-product-widget'                  => __( 'Custom Template', $this->plugin_name )
		    ],
		    'timeout'                  => 4000,
		    'path_order_creation'      => 'orders',
		    'path_load_quote'          => 'loan-quotes',
		    'load_quote_cds_js'        => 'https://cdn.soisy.it/loan-quote-widget.js',
		    'ajax_listener'            => plugin_dir_url( '' ) . $this->plugin_name . '/soisy-listener.php?action=order_status'
	    ];
    }
	
	/**
	 * Retrieves the list of product categories for select2 dropdown
	 *
	 * @param $res
	 *
	 * @return array
	 */
	public function get_admin_category_list( $res = [] ) {
		$args =[
			'taxonomy'     => [ 'product_cat' ],
			'hierarchical' => true,
			'order'        => 'ASC',
			'orderby'      => 'name',
			'fields'       => 'all',
			'hide_empty'   => false,
			'get'          => 'all',
		];
		
		// The Term Query
		$term_query     = new WP_Term_Query( $args );
		$admin_products = [];
		$filtered       = [];
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
	
	/**
	 * Register the stylesheets for the admin area.
	 *
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/soisy-pagamento-rateale-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		/**
		 * we may need this for debug purposes
		 */
		/*add_action( 'woocommerce_order_actions_start', function () {
			$order = wc_get_order();
			do_action( 'qm/debug', get_post_meta( $order->get_id(), 'soisy_orderToken', true ) );
		} );*/
		wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/soisy-pagamento-rateale-admin.js', ['jquery', 'select2'], $this->version, true );
		
		if ( is_object( get_current_screen() ) && get_current_screen()->base == 'woocommerce_page_wc-settings' ) {
			$admin_vars['haystacks']['allCats'] = $this->get_admin_category_list();
			wp_localize_script( $this->plugin_name,
				'adminVars',
				$admin_vars
			);
		}
		
		wp_enqueue_script( $this->plugin_name );
	}
	
	/**
	 * Enqueues Soisy Gateway to payment methods
	 *
	 * @param $methods
	 *
	 * @return mixed
	 */
	public function payment_methods( $methods ) {
		$methods[] = 'Soisy_Pagamento_Rateale_Gateway_Settings';
		
		return $methods;
	}
	
	/**
	 * Provides a shorthand link to Plugin Settings from Plugin Page
	 *
	 * @param $links
	 *
	 * @return array|string[]
	 */
	public function add_soisy_action_links( $links, $plugin_file ) {
		if ( str_starts_with( $plugin_file, $this->plugin_name ) ) {
			$link = get_admin_url( null, 'admin.php' ) . '?' . http_build_query( [
					'page'    => 'wc-settings',
					'tab'     => 'checkout',
					'section' => 'soisy',
				] );
			
			return array_merge( [ "<a href='$link'>Settings</a>" ], $links );
		}
		
		return $links;
   }

}
