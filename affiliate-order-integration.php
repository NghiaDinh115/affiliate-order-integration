<?php
/**
 * Plugin Name: Affiliate Order Integration
 * Plugin URI: https://github.com/NghiaDinh115/affiliate-order-integration
 * Description: Plugin tích hợp gửi order đến website affiliate. Hỗ trợ đồng bộ đơn hàng WooCommerce với các affiliate network.
 * Version: 1.0.0
 * Author: Sointech
 * Author URI: https://sointech.sointech.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: affiliate-order-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 *
 * @package AffiliateOrderIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AOI_PLUGIN_FILE', __FILE__ );
define( 'AOI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AOI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AOI_PLUGIN_VERSION', '1.0.0' );
define( 'AOI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader.
if ( file_exists( AOI_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once AOI_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Main Plugin Class
 *
 * @package AffiliateOrderIntegration
 */
class AffiliateOrderIntegration {

	/**
	 * Instance duy nhất của class
	 *
	 * @var AffiliateOrderIntegration|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return AffiliateOrderIntegration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - khởi tạo plugin
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Khởi tạo plugin sau khi WordPress loaded
	 *
	 * @return void
	 */
	public function init() {
		// Kiểm tra WooCommerce có active không
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		load_plugin_textdomain( 'affiliate-order-integration', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include các file cần thiết
	 *
	 * @return void
	 */
	private function includes() {
		// Always include required files with error checking
		$required_files = array(
			AOI_PLUGIN_PATH . 'includes/class-order-handler.php',
			AOI_PLUGIN_PATH . 'includes/class-affiliate-api.php',
			AOI_PLUGIN_PATH . 'includes/class-admin.php',
			AOI_PLUGIN_PATH . 'includes/class-google-sheets.php',
		);
		
		foreach ( $required_files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Khởi tạo hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Initialize Order Handler safely
		if ( class_exists( 'AOI_Order_Handler' ) ) {
			AOI_Order_Handler::get_instance();
		}
		
		// Initialize Google Sheets safely
		if ( class_exists( 'AOI_Google_Sheets' ) ) {
			new AOI_Google_Sheets();
		}
		
		// Initialize Admin safely
		if ( is_admin() && class_exists( 'AOI_Admin' ) ) {
			AOI_Admin::get_instance();
		}
	}

	/**
	 * Kích hoạt plugin
	 *
	 * @return void
	 */
	public function activate() {
		// Check WooCommerce trước khi activate
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				'<h1>' . esc_html__( 'Plugin Activation Error', 'affiliate-order-integration' ) . '</h1>' .
				'<p>' . esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'affiliate-order-integration' ) . '</p>' .
				'<p><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Return to plugins page', 'affiliate-order-integration' ) . '</a></p>',
				esc_html__( 'Plugin Activation Error', 'affiliate-order-integration' ),
				array( 'back_link' => true )
			);
		}
		
		// Include required files before using classes
		$this->includes();
		
		$this->create_tables();
		$this->create_default_options();

		// Tạo thư mục logs cho affiliate API (if class exists)
		if ( class_exists( 'AOI_Affiliate_API' ) ) {
			AOI_Affiliate_API::activate();
		}

		flush_rewrite_rules();
	}

	/**
	 * Hủy kích hoạt plugin
	 *
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Tạo bảng database cần thiết
	 *
	 * @return void
	 */
	private function create_tables() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'aoi_affiliate_orders';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            affiliate_url varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            response_data text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_id (order_id)
        ) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Tạo các options mặc định
	 *
	 * @return void
	 */
	private function create_default_options() {
		$default_options = array(
			'partner_id'       => '1',
			'auto_send_orders' => '1',
			'order_status'     => 'completed',
		);
		add_option( 'aoi_options', $default_options );

		// Tạo app key option riêng
		add_option( 'aff_app_key', '' );
	}

	/**
	 * Thông báo khi WooCommerce chưa được kích hoạt
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p><strong>' . esc_html__( 'Affiliate Order Integration', 'affiliate-order-integration' ) . '</strong> ' . esc_html__( 'requires WooCommerce to be installed and active.', 'affiliate-order-integration' ) . '</p></div>';
	}
}

AffiliateOrderIntegration::get_instance();
