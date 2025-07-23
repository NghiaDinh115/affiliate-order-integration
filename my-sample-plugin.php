<?php
/**
 * Plugin Name: My Sample Plugin
 * Plugin URI: https://yourwebsite.com/my-sample-plugin
 * Description: Plugin WordPress mẫu có thể tái sử dụng với các tính năng cơ bản
 * Version: 1.0.0
 * Author: Sointech
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-sample-plugin
 * Domain Path: /languages
 *
 * @package MySamplePlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MSP_PLUGIN_VERSION', '1.0.0' );
define( 'MSP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader.
if ( file_exists( MSP_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once MSP_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Main Plugin Class
 *
 * @package MySamplePlugin
 */
class MySamplePlugin {

	/**
	 * Instance duy nhất của class
	 *
	 * @var MySamplePlugin|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return MySamplePlugin
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
		load_plugin_textdomain( 'my-sample-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include các file cần thiết
	 *
	 * @return void
	 */
	private function includes() {
		// Use autoloader if available, otherwise fallback to manual includes.
		if ( class_exists( 'MySamplePlugin\\Core' ) ) {
			// Classes are autoloaded via Composer.
			return;
		}
		
		// Manual includes for traditional installation.
		require_once MSP_PLUGIN_PATH . 'includes/class-plugin-core.php';
		require_once MSP_PLUGIN_PATH . 'includes/class-admin.php';
		require_once MSP_PLUGIN_PATH . 'includes/class-frontend.php';
	}

	/**
	 * Khởi tạo hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		MSP_Core::get_instance();
		if ( is_admin() ) {
			MSP_Admin::get_instance();
		}
		if ( ! is_admin() ) {
			MSP_Frontend::get_instance();
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
		$table_name      = $wpdb->prefix . 'msp_data';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(100) NOT NULL,
            content text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
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
			'enable_feature_1' => '1',
			'enable_feature_2' => '0',
			'custom_message'   => 'Chào mừng bạn đến với plugin của tôi!',
		);
		add_option( 'msp_options', $default_options );
	}
}

MySamplePlugin::get_instance();
