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
		// Kiểm tra nếu classes đã được autoload bởi Composer
		if ( class_exists( 'AOI_Order_Handler' ) && class_exists( 'AOI_Affiliate_API' ) && class_exists( 'AOI_Admin' ) ) {
			// Classes đã được autoload, không cần include thủ công
			return;
		}
		
		// Manual includes nếu không dùng Composer
		require_once AOI_PLUGIN_PATH . 'includes/class-order-handler.php';
		require_once AOI_PLUGIN_PATH . 'includes/class-affiliate-api.php';
		require_once AOI_PLUGIN_PATH . 'includes/class-admin.php';
	}

	/**
	 * Khởi tạo hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		AOI_Order_Handler::get_instance();
		if ( is_admin() ) {
			AOI_Admin::get_instance();
		}
	}

	/**
	 * Kích hoạt plugin
	 *
	 * @return void
	 */
	public function activate() {
		$this->create_tables();
		$this->create_default_options();
		
		// Tạo thư mục logs cho affiliate API
		AOI_Affiliate_API::activate();
		
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
