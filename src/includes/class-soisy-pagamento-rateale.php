<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 *
 * @package    Soisy_Pagamento_Rateale
 * @subpackage Soisy_Pagamento_Rateale/includes
 * @author     Mirko Bianco <mirko@acmemk.com>
 */
class Soisy_Pagamento_Rateale {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @access   protected
	 * @var      Soisy_Pagamento_Rateale_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 */
	public function __construct() {
		if ( defined( 'SOISY_PAGAMENTO_RATEALE_VERSION' ) ) {
			$this->version = SOISY_PAGAMENTO_RATEALE_VERSION;
		} else {
			$this->version = '1.0.2';
		}
		$this->plugin_name = 'soisy-pagamento-rateale';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Soisy_Pagamento_Rateale_Loader. Orchestrates the hooks of the plugin.
	 * - Soisy_Pagamento_Rateale_i18n. Defines internationalization functionality.
	 * - Soisy_Pagamento_Rateale_Admin. Defines all hooks for the admin area.
	 * - Soisy_Pagamento_Rateale_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-soisy-pagamento-rateale-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-soisy-pagamento-rateale-i18n.php';
        /**
         * The class responsible for defining settings
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-soisy-pagamento-rateale-gateway-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-soisy-pagamento-rateale-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-soisy-pagamento-rateale-public.php';
		
		
		$this->loader = new Soisy_Pagamento_Rateale_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Soisy_Pagamento_Rateale_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Soisy_Pagamento_Rateale_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Soisy_Pagamento_Rateale_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_filter( 'woocommerce_payment_gateways', $plugin_admin, 'payment_methods' );
		
        $this->loader->add_filter( 'soisy_settings', $plugin_admin, 'soisy_vars' );
		
		$this->loader->add_filter('plugin_action_links', $plugin_admin,'add_soisy_action_links', 10, 2);
		
		/*
		 * Payments Gateways extend WC_Payment_Gateway
		 * To have them work properly they must be hooked to 'plugins_loaded'
		 */
        add_action( 'plugins_loaded', 'init_Soisy_Pagamento_Rateale_Gateway_Settings');
	}
	
	
	/**
	 * Register all the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @access   private
	 */
	private function define_public_hooks() {
		
		$plugin_public = new Soisy_Pagamento_Rateale_Public( $this->get_plugin_name(), $this->get_version() );
		
		
		$this->loader->add_action( 'woocommerce_before_cart_table', $plugin_public, 'updated_cart' );
		$this->loader->add_action( 'woocommerce_before_checkout_form', $plugin_public, 'updated_cart' );
		//$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp', $plugin_public, 'soisy_available' , 10);
		$this->loader->add_action( 'init', $plugin_public, 'shortcodes' , 10);
		$this->loader->add_filter( 'soisy_settings', $plugin_public, 'get_options',99);
		$this->loader->add_action( 'wp', $plugin_public, 'init_soisy_widget' );
		$this->loader->add_action( 'woocommerce_before_single_product', $plugin_public, 'product_hooks' );
		//$this->loader->add_action( 'woocommerce_before_main_content', $plugin_public, 'product_hooks' );
		$this->loader->add_action( 'woocommerce_proceed_to_checkout', $plugin_public, 'checkout_hooks', 1 );
		$this->loader->add_action( 'woocommerce_review_order_before_order_total', $plugin_public, 'order_review_hooks', 1 );
		$this->loader->add_action( 'soisy_render_widget', $plugin_public, 'render_widget', 1 );
		//ajax actions
		$this->loader->add_action( 'soisy_ajax_order_status', $plugin_public, 'parseRemoteRequest');
		
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Soisy_Pagamento_Rateale_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
