<?php
/**
 * Xử lý phần admin của plugin
 *
 * @package AffiliateOrderIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AOI_Admin
 * Xử lý tất cả chức năng admin của plugin
 */
class AOI_Admin {

	/**
	 * Instance duy nhất của class
	 *
	 * @var AOI_Admin|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return AOI_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - khởi tạo hooks
	 */
	private function __construct() {
		$this->init_hooks();
		// Debug mode - uncomment để debug
		add_action( 'admin_notices', array( $this, 'debug_admin_notices' ) );
		
		// Debug save process
		add_action( 'updated_option', array( $this, 'debug_option_update' ), 10, 3 );
	}

	/**
	 * Khởi tạo các hooks cho admin
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_aoi_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_aoi_resend_order', array( $this, 'ajax_resend_order' ) );

		// Thêm Settings link trong trang Plugins
		add_filter( 'plugin_action_links_' . plugin_basename( AOI_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

		// Thêm cột affiliate status vào danh sách đơn hàng WooCommerce (tương thích với COT và legacy)
		$this->init_order_columns();

		// Thêm meta box cho order edit page (tương thích với COT và legacy)
		$this->init_order_meta_box();
	}

	/**
	 * Khởi tạo order columns tương thích với COT và legacy
	 */
	private function init_order_columns() {
		// Kiểm tra settings trước khi add hooks
		$hooks_options = get_option( 'aoi_hooks_options', array() );
		$enable_columns = isset( $hooks_options['enable_order_columns'] ) ? $hooks_options['enable_order_columns'] : '1';

		if ( '1' !== $enable_columns ) {
			return; // Không cần thêm cột nếu không được bật
		}

		// Force hooks với priority cao để đảm bảo chúng được add
		add_action( 'current_screen', array( $this, 'setup_order_columns_on_screen' ) );
		add_action( 'load-edit.php', array( $this, 'setup_order_columns_on_load' ) );
		add_action( 'load-woocommerce_page_wc-orders', array( $this, 'setup_order_columns_on_load' ) );
		
		// Multiple hooks với priority cao để đảm bảo tương thích
		
		// Legacy post-based orders
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_affiliate_column' ), 999 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'affiliate_column_content' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'affiliate_column_sortable' ), 999 );
		
		// New HPOS (High-Performance Order Storage) hooks
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_affiliate_column' ), 999 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'affiliate_column_content_hpos' ), 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', array( $this, 'affiliate_column_sortable' ), 999 );
	}

	/**
	 * Khởi tạo order meta box tương thích với COT và legacy
	 */
	private function init_order_meta_box() {
		// Kiểm tra settings trước khi add hooks
		$hooks_options = get_option( 'aoi_hooks_options', array() );
		$enable_meta_boxes = isset( $hooks_options['enable_meta_boxes'] ) ? $hooks_options['enable_meta_boxes'] : '1';

		if ( '1' !== $enable_meta_boxes ) {
			return; // Không cần thêm meta box nếu không được bật
		}

		// Meta box hooks work for both COT and legacy
		add_action( 'add_meta_boxes', array( $this, 'add_affiliate_meta_box' ) );
		
		// Process meta for both COT and legacy
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_affiliate_meta_box' ) );
		
		// COT specific hook if available
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			add_action( 'woocommerce_update_order', array( $this, 'save_affiliate_meta_box' ) );
		}
	}

	/**
	 * Thêm Settings link vào trang Plugins
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=affiliate-order-integration' ) . '">' . __( 'Settings', 'affiliate-order-integration' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Thêm menu admin
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Affiliate Order Integration', 		// page title
			'Affiliate Orders',					// menu title
			'manage_options',					// capability
			'affiliate-order-integration',		// menu slug
			array( $this, 'main_admin_page' ),	// callback
			'dashicons-networking',				// icon
			30									// position	
		);

		// add_submenu_page(
		// 	'woocommerce',
		// 	__( 'Affiliate Order Logs', 'affiliate-order-integration' ),
		// 	__( 'Affiliate Logs', 'affiliate-order-integration' ),
		// 	'manage_options',
		// 	'affiliate-order-logs',
		// 	array( $this, 'logs_page' )
		// );

		// Submenu 1: Settings (API Configuration)
		add_submenu_page(
			'affiliate-order-integration',    		// parent slug
			'API Settings',							// page title	
			'API Settings',							// menu title
			'manage_options',						// capability
			'affiliate-order-integration',			// same as parent = default page
			array( $this, 'settings_page' )
		);

		// Submenu 2: Hooks Management
		add_submenu_page(
			'affiliate-order-integration',
			'Hooks Management',
			'Hooks Management', 
			'manage_options',
			'aoi-hooks-management',
			array( $this, 'hooks_management_page' )
		);

		// Submenu 3: Logs (di chuyển từ WooCommerce)
		add_submenu_page(
			'affiliate-order-integration',
			'Order Logs',
			'Order Logs',
			'manage_options', 
			'aoi-order-logs',
			array( $this, 'logs_page' )
		);
	}

	/**
	 * Đăng ký settings
	 */
	public function register_settings() {
		register_setting( 'aoi_settings', 'aoi_options', array( $this, 'sanitize_aoi_options' ) );
		register_setting( 'aoi_settings', 'aff_app_key' );
		register_setting( 'aoi_hooks_settings', 'aoi_hooks_options', array( $this, 'sanitize_hooks_options' ) );

		add_settings_section(
			'aoi_general_section',
			__( 'Sellmate Affiliate Settings', 'affiliate-order-integration' ),
			array( $this, 'general_section_callback' ),
			'aoi_settings'
		);

		add_settings_section(
			'aoi_hooks_section',
			__( 'Hooks Management', 'affiliate-order-integration' ),
			array( $this, 'hooks_section_callback' ),
			'aoi_hooks_settings'
		);

		add_settings_field(
			'partner_id',
			__( 'Partner ID', 'affiliate-order-integration' ),
			array( $this, 'partner_id_callback' ),
			'aoi_settings',
			'aoi_general_section'
		);

		add_settings_field(
			'app_key',
			__( 'App Key', 'affiliate-order-integration' ),
			array( $this, 'app_key_callback' ),
			'aoi_settings',
			'aoi_general_section'
		);

		add_settings_field(
			'auto_send_orders',
			__( 'Auto Send Orders', 'affiliate-order-integration' ),
			array( $this, 'auto_send_orders_callback' ),
			'aoi_settings',
			'aoi_general_section'
		);

		add_settings_field(
			'order_status',
			__( 'Send on Order Status', 'affiliate-order-integration' ),
			array( $this, 'order_status_callback' ),
			'aoi_settings',
			'aoi_general_section'
		);

		add_settings_field(
			'enable_order_columns',
			__( 'Enable Order Columns', 'affiliate-order-integration' ),
			array( $this, 'enable_order_columns_callback' ),
			'aoi_hooks_settings',
			'aoi_hooks_section'
		);

		add_settings_field(
			'enable_meta_boxes',
			__( 'Enable Meta Boxes', 'affiliate-order-integration' ),
			array( $this, 'enable_meta_boxes_callback' ),
			'aoi_hooks_settings',
			'aoi_hooks_section'
		);

		// Google Sheets settings section
		add_settings_section(
			'aoi_google_sheets_section',
			__( 'Google Sheets Integration', 'affiliate-order-integration' ),
			array( $this, 'google_sheets_section_callback' ),
			'aoi_settings'
		);

		add_settings_field(
			'enable_google_sheets',
			__( 'Enable Google Sheets', 'affiliate-order-integration' ),
			array( $this, 'enable_google_sheets_callback' ),
			'aoi_settings',
			'aoi_google_sheets_section'
		);

		add_settings_field(
			'google_form_url',
			__( 'Google Form URL', 'affiliate-order-integration' ),
			array( $this, 'google_form_url_callback' ),
			'aoi_settings',
			'aoi_google_sheets_section'
		);

		// Custom JavaScript Display settings section
		add_settings_section(
			'aoi_custom_js_display_section',
			__( 'Custom JavaScript Discount Display', 'affiliate-order-integration' ),
			array( $this, 'custom_js_display_section_callback' ),
			'aoi_settings'
		);

		add_settings_field(
			'enable_custom_js_display',
			__( 'Enable Custom JavaScript Display', 'affiliate-order-integration' ),
			array( $this, 'enable_custom_js_display_callback' ),
			'aoi_settings',
			'aoi_custom_js_display_section'
		);

		add_settings_field(
			'custom_js_code',
			__( 'Custom JavaScript Code', 'affiliate-order-integration' ),
			array( $this, 'custom_js_code_callback' ),
			'aoi_settings',
			'aoi_custom_js_display_section'
		);

		add_settings_field(
			'custom_js_pages',
			__( 'Pages to Load JavaScript', 'affiliate-order-integration' ),
			array( $this, 'custom_js_pages_callback' ),
			'aoi_settings',
			'aoi_custom_js_display_section'
		);

	}

	/**
	 * General section callback
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure your Sellmate affiliate network settings.', 'affiliate-order-integration' ) . '</p>';
		echo '<p>' . esc_html__( 'API URL: https://aff-api.sellmate.vn/api/v1/partnerSystem/orderCreate', 'affiliate-order-integration' ) . '</p>';
	}

	/**
	 * Partner ID field callback
	 */
	public function partner_id_callback() {
		$options = get_option( 'aoi_options', array() );
		$value   = isset( $options['partner_id'] ) ? $options['partner_id'] : '1';
		?>
		<input type="number" id="partner_id" name="aoi_options[partner_id]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" min="1" />
		<p class="description"><?php esc_html_e( 'Enter your partner ID in the affiliate system.', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * App Key field callback
	 */
	public function app_key_callback() {
		$app_key = get_option( 'aff_app_key', '' );
		?>
		<input type="password" id="app_key" name="aff_app_key" value="<?php echo esc_attr( $app_key ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Enter your app key for token verification (optional).', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * Auto send orders callback
	 */
	public function auto_send_orders_callback() {
		$options = get_option( 'aoi_options', array() );
		$value   = isset( $options['auto_send_orders'] ) ? $options['auto_send_orders'] : '1';
		?>
		<input type="checkbox" id="auto_send_orders" name="aoi_options[auto_send_orders]" value="1" <?php checked( '1', $value ); ?> />
		<label for="auto_send_orders"><?php esc_html_e( 'Automatically send orders to affiliate', 'affiliate-order-integration' ); ?></label>
		<p class="description"><?php esc_html_e( 'When enabled, orders will be automatically sent to affiliate network when reaching the selected status.', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * Order status callback
	 */
	public function order_status_callback() {
		$options = get_option( 'aoi_options', array() );
		$value   = isset( $options['order_status'] ) ? $options['order_status'] : 'completed';
		?>
		<select id="order_status" name="aoi_options[order_status]">
			<option value="processing" <?php selected( 'processing', $value ); ?>><?php esc_html_e( 'Processing', 'affiliate-order-integration' ); ?></option>
			<option value="on-hold" <?php selected( 'on-hold', $value ); ?>><?php esc_html_e( 'On Hold', 'affiliate-order-integration' ); ?></option>
			<option value="completed" <?php selected( 'completed', $value ); ?>><?php esc_html_e( 'Completed', 'affiliate-order-integration' ); ?></option>
			<option value="cancelled" <?php selected( 'cancelled', $value ); ?>><?php esc_html_e( 'Cancelled', 'affiliate-order-integration' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Select when to send orders to affiliate.', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * Hooks section callback
	 */
	public function hooks_section_callback() {
		echo '<p>' . esc_html__( 'Control which hooks are active for the plugin.', 'affiliate-order-integration' ) . '</p>';
	}

	/**
	 * Enable order columns callback
	 */
	public function enable_order_columns_callback() {
		$options = get_option( 'aoi_hooks_options', array() );
		$value = isset( $options['enable_order_columns'] ) ? $options['enable_order_columns'] : '1';
		?>
		<input type="checkbox" id="enable_order_columns" name="aoi_hooks_options[enable_order_columns]" value="1" <?php checked( '1', $value ); ?> />
		<label for="enable_order_columns"><?php esc_html_e( 'Add affiliate status column to WooCommerce orders list', 'affiliate-order-integration' ); ?></label>
		<?php
	}

	/**
	 * Enable meta boxes callback
	 */
	public function enable_meta_boxes_callback() {
		$options = get_option( 'aoi_hooks_options', array() );
		$value = isset( $options['enable_meta_boxes'] ) ? $options['enable_meta_boxes'] : '1';
		?>
		<input type="checkbox" id="enable_meta_boxes" name="aoi_hooks_options[enable_meta_boxes]" value="1" <?php checked( '1', $value ); ?> />
		<label for="enable_meta_boxes"><?php esc_html_e( 'Add affiliate info meta box to order edit pages', 'affiliate-order-integration' ); ?></label>
		<?php
	}

	/**
	 * Settings page
	 */
	public function settings_page() {
		?>
		<div class="wrap aoi-admin-wrap">
			<h1><?php esc_html_e( 'Affiliate Order Integration - API Settings', 'affiliate-order-integration' ); ?></h1>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'aoi_settings' );
				do_settings_sections( 'aoi_settings' );
				submit_button();
				?>
			</form>
			
			<div class="card">
				<h2><?php esc_html_e( 'Test Connection', 'affiliate-order-integration' ); ?></h2>
				<p><?php esc_html_e( 'Test your affiliate API connection.', 'affiliate-order-integration' ); ?></p>
				<button type="button" id="test-connection" class="button button-secondary"><?php esc_html_e( 'Test Connection', 'affiliate-order-integration' ); ?></button>
				<div id="test-result" style="margin-top: 10px;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Logs page
	 */
	public function logs_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';

		$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 100" );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Affiliate Order Logs', 'affiliate-order-integration' ); ?></h1>
			
			<table class="wp-list-table widefat fixed striped aoi-logs-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order ID', 'affiliate-order-integration' ); ?></th>
						<th><?php esc_html_e( 'Status', 'affiliate-order-integration' ); ?></th>
						<th><?php esc_html_e( 'Affiliate URL', 'affiliate-order-integration' ); ?></th>
						<th><?php esc_html_e( 'Sent At', 'affiliate-order-integration' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'affiliate-order-integration' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $logs ) : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $log->order_id . '&action=edit' ) ); ?>">#<?php echo esc_html( $log->order_id ); ?></a></td>
								<td>
									<span class="<?php echo 'sent' === $log->status ? 'status-success' : 'status-error'; ?>">
										<?php echo esc_html( ucfirst( $log->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $log->affiliate_url ); ?></td>
								<td><?php echo esc_html( $log->sent_at ); ?></td>
								<td>
									<?php if ( 'failed' === $log->status ) : ?>
										<button type="button" class="button button-small resend-order" data-order-id="<?php echo esc_attr( $log->order_id ); ?>">
											<?php esc_html_e( 'Resend', 'affiliate-order-integration' ); ?>
										</button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5"><?php esc_html_e( 'No logs found.', 'affiliate-order-integration' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Main admin page
	 */
	public function main_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Affiliate Order Integration', 'affiliate-order-integration' ); ?></h1>
			<p><?php esc_html_e( 'Welcome to the Affiliate Order Integration plugin. Please configure your settings.', 'affiliate-order-integration' ); ?></p>
			<p><?php esc_html_e( 'Use the menu on the left to access settings, logs, and hooks management.', 'affiliate-order-integration' ); ?></p>

			<div class="aoi-admin-cards">
				<div class="card">
					<h2><?php esc_html_e( 'Quick Actions', 'affiliate-order-integration' ); ?></h2>
					<p>
						<a href="<?php echo admin_url('admin.php?page=affiliate-order-integration'); ?>" class="button button-primary">API Settings</a>
						<a href="<?php echo admin_url('admin.php?page=aoi-hooks-management'); ?>" class="button">Hooks Management</a>
						<a href="<?php echo admin_url('admin.php?page=aoi-order-logs'); ?>" class="button">View Logs</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Hooks management page
	 */
	public function hooks_management_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Hooks Management', 'affiliate-order-integration' ); ?></h1>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'aoi_hooks_settings' );
					do_settings_sections( 'aoi_hooks_settings' );
					submit_button();
				?>
			</form>

			<div class="card">
				<h2><?php esc_html_e( 'Current Hook Status', 'affiliate-order-integration' ); ?></h2>
				<p><?php esc_html_e( 'Check which hooks are currently active:', 'affiliate-order-integration' ); ?></p>
				<?php $this->display_hook_status(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $post_type;

		// Load on settings page, logs page, and order list/edit pages
		$load_scripts = false;
		
		// Settings và logs pages
		if ( strpos( $hook, 'affiliate-order-integration' ) !== false ||
			strpos( $hook, 'aoi-hooks-management' ) !== false ||
			strpos( $hook, 'aoi-order-logs' ) !== false ) {
			$load_scripts = true;
		}
		
		// Legacy shop_order pages
		if ( 'shop_order' === $post_type ||
			( 'edit.php' === $hook && 'shop_order' === $post_type ) ) {
			$load_scripts = true;
		}
		
		// HPOS order pages
		if ( 'woocommerce_page_wc-orders' === $hook ||
			strpos( $hook, 'wc-orders' ) !== false ) {
			$load_scripts = true;
		}
		
		// WooCommerce admin pages
		if ( strpos( $hook, 'woocommerce' ) !== false ) {
			$load_scripts = true;
		}

		if ( $load_scripts ) {
			wp_enqueue_style( 'aoi-admin', AOI_PLUGIN_URL . 'admin/css/admin.css', array(), AOI_PLUGIN_VERSION );
			wp_enqueue_script( 'aoi-admin', AOI_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), AOI_PLUGIN_VERSION, true );
			wp_localize_script(
				'aoi-admin',
				'aoi_ajax',
				array(
					'url'   => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'aoi_ajax_nonce' ),
				)
			);
		}
	}

	/**
	 * AJAX test connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'aoi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$api    = new AOI_Affiliate_API();
		$result = $api->test_connection();

		wp_send_json( $result );
	}

	/**
	 * AJAX resend order
	 */
	public function ajax_resend_order() {
		check_ajax_referer( 'aoi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$order_id = intval( $_POST['order_id'] );

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order ID', 'affiliate-order-integration' ) ) );
		}

		$handler = AOI_Order_Handler::get_instance();
		$result  = $handler->send_order_to_affiliate( $order_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Order resent successfully', 'affiliate-order-integration' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to resend order', 'affiliate-order-integration' ) ) );
		}
	}

	/**
	 * Thêm cột affiliate status vào danh sách đơn hàng
	 */
	public function add_affiliate_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Thêm cột affiliate status sau cột order_status hoặc order_total
			if ( 'order_status' === $key || 'order_total' === $key ) {
				$new_columns['affiliate_status'] = __( 'Affiliate Status', 'affiliate-order-integration' );
			}
		}
		
		// Nếu không tìm thấy order_status hoặc order_total, thêm vào cuối
		if ( ! isset( $new_columns['affiliate_status'] ) ) {
			$new_columns['affiliate_status'] = __( 'Affiliate Status', 'affiliate-order-integration' );
		}
		
		return $new_columns;
	}

	/**
	 * Hiển thị nội dung cột affiliate status cho HPOS (High-Performance Order Storage)
	 */
	public function affiliate_column_content_hpos( $column_name, $order ) {
		if ( 'affiliate_status' !== $column_name ) {
			return;
		}

		// HPOS có thể truyền order object hoặc order ID
		if ( is_object( $order ) ) {
			$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->ID;
		} else {
			$order_id = $order;
		}
		
		$this->display_affiliate_status( $order_id );
	}

	/**
	 * Hiển thị nội dung cột affiliate status
	 */
	public function affiliate_column_content( $column_name, $order_id ) {
		if ( 'affiliate_status' !== $column_name ) {
			return;
		}
		
		$this->display_affiliate_status( $order_id );
	}

	/**
	 * Hiển thị affiliate status (shared logic)
	 */
	private function display_affiliate_status( $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';

		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %d", $order_id ) );

		if ( $log ) {
			if ( 'sent' === $log->status ) {
				echo '<span style="color: #00a32a; font-weight: bold;">✓ ' . esc_html__( 'Sent', 'affiliate-order-integration' ) . '</span>';
				echo '<br><small>' . esc_html( $log->sent_at ) . '</small>';
			} elseif ( 'rollback' === $log->status ) {
				echo '<span style="color: #ff8c00; font-weight: bold;">↩ ' . esc_html__( 'Rollback', 'affiliate-order-integration' ) . '</span>';
				echo '<br><small>' . esc_html__( 'Status changed back', 'affiliate-order-integration' ) . '</small>';
				echo '<br><button type="button" class="button button-small resend-order" data-order-id="' . esc_attr( $order_id ) . '">' . esc_html__( 'Resend', 'affiliate-order-integration' ) . '</button>';
			} else {
				echo '<span style="color: #d63638; font-weight: bold;">✗ ' . esc_html__( 'Failed', 'affiliate-order-integration' ) . '</span>';
				echo '<br><button type="button" class="button button-small resend-order" data-order-id="' . esc_attr( $order_id ) . '">' . esc_html__( 'Resend', 'affiliate-order-integration' ) . '</button>';
			}
		} else {
			// Kiểm tra có CTV token không - tương thích với COT và legacy
			$ctv_token = $this->get_order_meta( $order_id, '_aoi_ctv_token' );
			if ( $ctv_token ) {
				echo '<span style="color: #f0ad4e;">⏳ ' . esc_html__( 'Pending', 'affiliate-order-integration' ) . '</span>';
				echo '<br><button type="button" class="button button-small resend-order" data-order-id="' . esc_attr( $order_id ) . '">' . esc_html__( 'Send Now', 'affiliate-order-integration' ) . '</button>';
			} else {
				echo '<span style="color: #6c757d;">— ' . esc_html__( 'No CTV', 'affiliate-order-integration' ) . '</span>';
			}
		}
	}

	/**
	 * Helper method để lấy order meta tương thích với COT và legacy
	 */
	private function get_order_meta( $order_id, $meta_key ) {
		// Thử get order object trước
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order && method_exists( $order, 'get_meta' ) ) {
				return $order->get_meta( $meta_key );
			}
		}
		
		// Fallback to post meta
		return get_post_meta( $order_id, $meta_key, true );
	}
	
	/**
	 * Làm cột Affiliate Status có thể sắp xếp
	 */
	public function affiliate_column_sortable( $column ) {
		$column['affiliate_status'] = 'affiliate_status';
		return $column;
	}

	/**
	 * Thêm meta box cho affiliate info vào order edit page
	 */
	public function add_affiliate_meta_box() {
		// Legacy post-based orders
		add_meta_box(
			'aoi_affiliate_info',
			__( 'Affiliate Information', 'affiliate-order-integration' ),
			array( $this, 'affiliate_meta_box_content' ),
			'shop_order',
			'side',
			'default'
		);
		
		// HPOS orders
		add_meta_box(
			'aoi_affiliate_info',
			__( 'Affiliate Information', 'affiliate-order-integration' ),
			array( $this, 'affiliate_meta_box_content' ),
			'woocommerce_page_wc-orders',
			'side',
			'default'
		);
	}

	/**
	 * Hiển thị nội dung meta box affiliate info
	 *
	 * @param WP_Post $post Post object.
	 */
	public function affiliate_meta_box_content( $post ) {
		$order_id = $post->ID;

		// Lấy thông tin từ database
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';
		$log        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %d", $order_id ) );

		// Lấy CTV token từ order meta (ưu tiên order meta trước)
		$ctv_token = $this->get_order_meta( $order_id, '_aoi_ctv_token' );

		echo '<div class="aoi-affiliate-info">';

		// CTV Token Info
		echo '<p><strong>' . esc_html__( 'CTV Token:', 'affiliate-order-integration' ) . '</strong></br>';
		if ( $ctv_token ) {
			echo '<code>' . esc_html( substr( $ctv_token, 0, 20 ) ) . '...</code>';
		} else {
			echo '<em>' . esc_html__( 'No CTV token found', 'affiliate-order-integration' ) . '</em>';
		}
		echo '</p>';

		// Affiliate Status
		echo '<p><strong>' . esc_html__( 'Affiliate Status:', 'affiliate-order-integration' ) . '</strong></br>';
		if ( $log ) {
			if ( 'sent' === $log->status ) {
				echo '<span style="color: #00a32a;">✓ ' . esc_html__( 'Successfully sent', 'affiliate-order-integration' ) . '</span>';
				echo '<br><small>' . esc_html__( 'Sent at:', 'affiliate-order-integration' ) . ' ' . esc_html( $log->sent_at ) . '</small>';
			} elseif ( 'rollback' === $log->status ) {
				echo '<span style="color: #ff8c00;">↩ ' . esc_html__( 'Status Rollback', 'affiliate-order-integration' ) . '</span>';
				echo '<br><small>' . esc_html__( 'Order was sent but status changed back', 'affiliate-order-integration' ) . '</small>';
				echo '<br><small style="color: #d63638;"><strong>' . esc_html__( '⚠ Affiliate order may still be active - manual cancellation needed', 'affiliate-order-integration' ) . '</strong></small>';
			} else {
				echo '<span style="color: #d63638;">✗ ' . esc_html__( 'Send failed', 'affiliate-order-integration' ) . '</span>';
				echo '<br><small>' . esc_html__( 'Last attempt:', 'affiliate-order-integration' ) . ' ' . esc_html( $log->sent_at ) . '</small>';
			}

			// Response data
			if ( $log->response_data ) {
				$response_data = json_decode( $log->response_data, true );
				
				// Special handling for rollback data
				if ( isset( $response_data['rollback'] ) && $response_data['rollback'] ) {
					echo '<br><details style="margin-top: 10px;"><summary>' . esc_html__( 'Rollback Details', 'affiliate-order-integration' ) . '</summary>';
					echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; font-size: 12px;">';
					echo '<strong>' . esc_html__( 'Status Change:', 'affiliate-order-integration' ) . '</strong> ';
					echo esc_html( $response_data['old_status'] ) . ' → ' . esc_html( $response_data['new_status'] ) . '<br>';
					echo '<strong>' . esc_html__( 'Rollback Time:', 'affiliate-order-integration' ) . '</strong> ';
					echo esc_html( $response_data['rollback_time'] ?? 'Unknown' ) . '<br>';
					echo '<strong>' . esc_html__( 'Original Response:', 'affiliate-order-integration' ) . '</strong><br>';
					if ( isset( $response_data['original_response'] ) ) {
						echo '<pre style="background: #f8f9fa; padding: 5px; margin-top: 5px; font-size: 10px; max-height: 150px; overflow-y: auto;">';
						echo esc_html( wp_json_encode( $response_data['original_response'], JSON_PRETTY_PRINT ) );
						echo '</pre>';
					}
					echo '</div></details>';
				} else {
					echo '<br><details style="margin-top: 10px;"><summary>' . esc_html__( 'Response Details', 'affiliate-order-integration' ) . '</summary>';
					echo '<pre style="background: #f5f5f5; padding: 10px; font-size: 11px; max-height: 200px; overflow-y: auto;">' . esc_html( wp_json_encode( $response_data, JSON_PRETTY_PRINT ) ) . '</pre>';
					echo '</details>';
				}
			}
		} elseif ( $ctv_token ) {
				echo '<span style="color: #f0ad4e;">⏳ ' . esc_html__( 'Not sent yet', 'affiliate-order-integration' ) . '</span>';
		} else {
			echo '<span style="color: #6c757d;">— ' . esc_html__( 'No CTV token', 'affiliate-order-integration' ) . '</span>';
		}
		echo '</p>';

		// Manual send button
		if ( $ctv_token ) {
			echo '<p>';
			echo '<button type="button" id="manual-send-affiliate" class="button button-secondary" data-order-id="' . esc_attr( $order_id ) . '">';
			echo esc_html__( 'Send to Affiliate Now', 'affiliate-order-integration' );
			echo '</button>';
			echo '</p>';
		}

		echo '</div>';

		// JavaScript for manual send button
		?>
		<script>
			jQuery(document).ready(function($) {
				$('#manual-send-affiliate').on('click', function() {
					var $button = $(this);
					var orderId = $button.data('order-id');

					if (!confirm('<?php echo esc_js( __( 'Send this order to affiliate network?', 'affiliate-order-integration' ) ); ?>')) {
						return;
					}

					$button.prop('disabled', true).text('<?php esc_js( __( 'Sending...', 'affiliate-order-integration' ) ); ?>');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'aoi_resend_order',
							nonce: '<?php echo esc_js( wp_create_nonce( 'aoi_ajax_nonce' ) ); ?>',
							order_id: orderId
						},
						success: function(response) {
							if (response.success) {
								alert('<?php echo esc_js( __( 'Order sent successfully!', 'affiliate-order-integration' ) ); ?>');
								location.reload();
							} else {
								alert('<?php echo esc_js( __( 'Failed to send order. Please try again.', 'affiliate-order-integration' ) ); ?>');

							}
						},
						error: function() {
							alert('<?php echo esc_js( __( 'Connection error. Please try again.', 'affiliate-order-integration' ) ); ?>');
						},
						complete: function() {
							$button.prop('disabled', false).text('<?php echo esc_js( __( 'Send to Affiliate Now', 'affiliate-order-integration' ) ); ?>');
						}
					})
				})
			})
		</script>
		<?php
	}

	/**
	 * Lưu thông tin từ affiliate meta box
	 */
	public function save_affiliate_meta_box( $order_id ) {
		// Có thể mở rộng để lưu settings riêng cho từng order
	}

	/**
	 * Debug admin notices để kiểm tra hooks
	 */
	public function debug_admin_notices() {
		global $hook_suffix, $post_type;
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-info"><p>';
			echo '<strong>AOI Debug:</strong> ';
			echo 'Hook: ' . esc_html( $hook_suffix ?? 'none' );
			echo ' | Post Type: ' . esc_html( $post_type ?? 'none' );
			echo ' | URL: ' . esc_html( $_SERVER['REQUEST_URI'] ?? 'none' );
			echo '</p></div>';
		}
	}

	/**
	 * Debug option updates to track when aoi_options is saved
	 */
	public function debug_option_update( $option_name, $old_value, $new_value ) {
		if ( $option_name === 'aoi_options' ) {
			error_log( '🔧 AOI OPTION UPDATE: aoi_options was saved!' );
			error_log( '🔧 AOI: Old custom_js_code exists: ' . ( isset( $old_value['custom_js_code'] ) ? 'YES' : 'NO' ) );
			error_log( '🔧 AOI: New custom_js_code exists: ' . ( isset( $new_value['custom_js_code'] ) ? 'YES' : 'NO' ) );
			
			if ( isset( $new_value['custom_js_code'] ) ) {
				error_log( '🔧 AOI: New custom_js_code length: ' . strlen( $new_value['custom_js_code'] ) );
				error_log( '🔧 AOI: New custom_js_code contains "Test Badge": ' . ( strpos( $new_value['custom_js_code'], 'Test Badge' ) !== false ? 'YES' : 'NO' ) );
				error_log( '🔧 AOI: New custom_js_code preview: ' . substr( $new_value['custom_js_code'], 0, 150 ) . '...' );
			}
		}
	}

	/**
	 * Setup order columns khi current_screen được load
	 */
	public function setup_order_columns_on_screen( $screen ) {
		if ( ! $screen ) {
			return;
		}

		// Check nếu đang ở order list pages
		if ( $screen->id === 'edit-shop_order' || 
			 $screen->id === 'woocommerce_page_wc-orders' ||
			 strpos( $screen->id, 'shop_order' ) !== false ||
			 strpos( $screen->id, 'wc-orders' ) !== false ) {
			
			// Force add hooks cho screen hiện tại
			add_filter( 'manage_' . $screen->id . '_columns', array( $this, 'add_affiliate_column' ), 999 );
		}
	}

	/**
	 * Setup order columns khi page được load
	 */
	public function setup_order_columns_on_load() {
		global $post_type;
		
		// Đảm bảo hooks được add ngay khi page load
		if ( $post_type === 'shop_order' || 
			 isset( $_GET['page'] ) && $_GET['page'] === 'wc-orders' ) {
			
			// Re-add hooks với priority cao nhất
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_affiliate_column' ), 9999 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'affiliate_column_content' ), 1, 2 );
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_affiliate_column' ), 9999 );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'affiliate_column_content_hpos' ), 1, 2 );
		}
	}

	/**
	 * Display hook status
	 */
	private function display_hook_status() {
		global $wp_filter;

		// Get current settings
		$hooks_options = get_option( 'aoi_hooks_options', array() );
		$enable_columns = isset( $hooks_options['enable_order_columns'] ) ? $hooks_options['enable_order_columns'] : '1';
		$enable_meta_boxes = isset( $hooks_options['enable_meta_boxes'] ) ? $hooks_options['enable_meta_boxes'] : '1';
		
		$hooks_to_check = [
			'manage_edit-shop_order_columns' => [
				'label' => 'Order Columns (Legacy)',
				'enabled_in_settings' => '1' === $enable_columns
			],
			'manage_woocommerce_page_wc-orders_columns' => [
				'label' => 'Order Columns (HPOS)',
				'enabled_in_settings' => '1' === $enable_columns
			],
        	'add_meta_boxes' => [
				'label' => 'Meta Boxes',
				'enabled_in_settings' => '1' === $enable_meta_boxes
			]
		];

		echo '<ul>';
		foreach ( $hooks_to_check as $hook => $config ) {
			$hook_registered = isset( $wp_filter[ $hook ] );
			$settings_enabled = $config['enabled_in_settings'];

			// Determine status
			if ( $settings_enabled && $hook_registered ) {
				$status = '<span style="color: #00a32a;">✓ Active</span>';
			} elseif ( ! $settings_enabled ) {
				$status = '<span style="color: #d63638;">✗ Disabled in Settings</span>';
			} elseif ( $settings_enabled && ! $hook_registered ) {
				$status = '<span style="color: #f0ad4e;">⏳ Not Registered</span>';
			} else {
				$status = '<span style="color: #6c757d;">— Unknown</span>';
			}
			
		    echo '<li><strong>' . esc_html( $config['label'] ) . ':</strong> ' . $status . '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Sanitize aoi options to handle checkbox values and other inputs
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized options.
	 */
	public function sanitize_aoi_options( $input ) {
		// Debug what's being submitted
		error_log( '🔧 AOI SANITIZE: Function called with input keys: ' . implode( ', ', array_keys( $input ) ) );
		if ( isset( $input['custom_js_code'] ) ) {
			error_log( '🔧 AOI SANITIZE: custom_js_code input preview: ' . substr( $input['custom_js_code'], 0, 100 ) . '...' );
		}
		
		$sanitized = array();

		// Get current options để merge với new values
		$current_options = get_option( 'aoi_options', array() );

		// Handle partner_id - integer, minimum 1
		$sanitized['partner_id'] = isset( $input['partner_id'] ) ? max( 1, intval( $input['partner_id'] ) ) : 1;

		// Handle auto_send_orders checkbox - 1 or 0 as string
		$sanitized['auto_send_orders'] = isset( $input['auto_send_orders'] ) && '1' === $input['auto_send_orders'] ? '1' : '0';

		// Handle order_status - whitelist allowed values
		$allowed_statuses = array( 'processing', 'on-hold', 'completed', 'cancelled' );
		$sanitized['order_status'] = isset( $input['order_status'] ) && in_array( $input['order_status'], $allowed_statuses ) 
			? $input['order_status'] 
			: 'completed';

		// Handle enable_google_sheets checkbox
		$sanitized['enable_google_sheets'] = isset( $input['enable_google_sheets'] ) && '1' === $input['enable_google_sheets'] ? '1' : '0';

		// Handle google_form_url - validate URL
		$sanitized['google_form_url'] = isset( $input['google_form_url'] ) ? esc_url_raw( $input['google_form_url'] ) : '';

		// Handle enable_custom_js_display checkbox
		$sanitized['enable_custom_js_display'] = isset( $input['enable_custom_js_display'] ) && '1' === $input['enable_custom_js_display'] ? '1' : '0';

		// Handle custom_js_code - allow JavaScript code but escape for storage
		if ( isset( $input['custom_js_code'] ) ) {
			$raw_code = $input['custom_js_code'];
			$sanitized['custom_js_code'] = wp_unslash( $raw_code );
			
			// Debug logging for custom JS code save
			error_log( '🔧 AOI SAVE DEBUG: Custom JS Code received' );
			error_log( '🔧 AOI: Raw input length: ' . strlen( $raw_code ) );
			error_log( '🔧 AOI: After wp_unslash length: ' . strlen( $sanitized['custom_js_code'] ) );
			error_log( '🔧 AOI: Contains "Test Badge": ' . ( strpos( $sanitized['custom_js_code'], 'Test Badge' ) !== false ? 'YES' : 'NO' ) );
			error_log( '🔧 AOI: Preview of saved code: ' . substr( $sanitized['custom_js_code'], 0, 200 ) . '...' );
			error_log( '🔧 AOI: MD5 hash of saved code: ' . md5( $sanitized['custom_js_code'] ) );
		} else {
			$sanitized['custom_js_code'] = '';
			error_log( '🔧 AOI SAVE DEBUG: No custom_js_code in input, setting to empty' );
		}

		// Handle custom_js_pages - array of allowed page types
		$allowed_pages = array( 'checkout', 'thankyou', 'cart' );
		$sanitized['custom_js_pages'] = array();
		if ( isset( $input['custom_js_pages'] ) && is_array( $input['custom_js_pages'] ) ) {
			foreach ( $input['custom_js_pages'] as $page ) {
				if ( in_array( $page, $allowed_pages ) ) {
					$sanitized['custom_js_pages'][] = $page;
				}
			}
		}

		// Check if auto_send_orders setting changed to show notice
		if ( isset( $current_options['auto_send_orders'] ) && 
			 $current_options['auto_send_orders'] !== $sanitized['auto_send_orders'] ) {
			$status = $sanitized['auto_send_orders'] === '1' ? 'enabled' : 'disabled';
			add_settings_error( 'aoi_options', 'auto_send_changed', 
				sprintf( __( 'Auto send orders has been %s successfully.', 'affiliate-order-integration' ), $status ), 
				'success' );
		}

		return $sanitized;
	}

	/**
	 * Sanitize hooks options to handle checkbox values
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized options.
	 */
	public function sanitize_hooks_options( $input ) {
		$sanitized = array();

		// Get current options để merge với new values
		$current_options = get_option( 'aoi_hooks_options', array(
			'enable_order_columns' => '1',
			'enable_meta_boxes' => '1'
		) );

		// Handle enable_order_columns checkbox
		$sanitized['enable_order_columns'] = isset( $input['enable_order_columns'] ) && '1' === $input['enable_order_columns'] ? '1' : '0';

		// Handle enable_meta_boxes checkbox  
		$sanitized['enable_meta_boxes'] = isset( $input['enable_meta_boxes'] ) && '1' === $input['enable_meta_boxes'] ? '1' : '0';

		// Check if settings changed to show notice
		if ( $current_options['enable_order_columns'] !== $sanitized['enable_order_columns'] || 
			 $current_options['enable_meta_boxes'] !== $sanitized['enable_meta_boxes'] ) {
			add_settings_error( 'aoi_hooks_options', 'hooks_changed', 
				__( 'Hooks settings updated successfully. Some changes may require page refresh to take effect.', 'affiliate-order-integration' ), 
				'success' );
		}

		return $sanitized;
	}

	/**
	 * Google Sheets section callback
	 */
	public function google_sheets_section_callback() {
    	echo '<p>' . esc_html__( 'Configure Google Sheets integration settings.', 'affiliate-order-integration' ) . '</p>';
	}

	/**
	 * Enable Google Sheets callback
	 */
	public function enable_google_sheets_callback() {
		$options = get_option( 'aoi_options', array() );
		$value = isset( $options['enable_google_sheets'] ) ? $options['enable_google_sheets'] : '1';
		?>
		<input type="checkbox" id="enable_google_sheets" name="aoi_options[enable_google_sheets]" value="1" <?php checked( '1', $value ); ?> />
		<label for="enable_google_sheets"><?php esc_html_e( 'Send orders to Google Sheets', 'affiliate-order-integration' ); ?></label>
		<?php
	}

	/**
	 * Google Form URL callback
	 */
	public function google_form_url_callback() {
		$options = get_option( 'aoi_options', array() );
		$value = isset( $options['google_form_url'] ) ? $options['google_form_url'] : '';
		?>
		<input type="url" id="google_form_url" name="aoi_options[google_form_url]" value="<?php echo esc_url( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Enter your Google Form URL to send orders.', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * Custom JavaScript Display section callback
	 */
	public function custom_js_display_section_callback() {
		echo '<p>' . esc_html__( 'Configure custom JavaScript code to display affiliate discounts anywhere on your website.', 'affiliate-order-integration' ) . '</p>';
		echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;">';
		echo '<h4 style="margin-top: 0;">📘 ' . esc_html__( 'Developer Guide', 'affiliate-order-integration' ) . '</h4>';
		echo '<p><strong>' . esc_html__( 'Global Variable Available:', 'affiliate-order-integration' ) . '</strong> <code>window.aoiDiscountData</code></p>';
		echo '<ul style="margin: 10px 0;">';
		echo '<li><code>aoiDiscountData.hasDiscount</code> - Boolean (true if discount available)</li>';
		echo '<li><code>aoiDiscountData.discountPercent</code> - Number (e.g., 15 for 15%)</li>';
		echo '<li><code>aoiDiscountData.discountAmount</code> - Number (calculated discount in VND)</li>';
		echo '<li><code>aoiDiscountData.formattedAmount</code> - String (formatted with currency)</li>';
		echo '<li><code>aoiDiscountData.linkId</code> - String (affiliate link ID)</li>';
		echo '</ul>';
		echo '<p><strong>' . esc_html__( 'Example:', 'affiliate-order-integration' ) . '</strong></p>';
		echo '<pre style="background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px;">if (window.aoiDiscountData && window.aoiDiscountData.hasDiscount) {
		document.querySelector(\'.my-selector\').innerHTML = 
			\'&lt;div&gt;You save: \' + window.aoiDiscountData.formattedAmount + \'&lt;/div&gt;\';
	}</pre>';
		echo '</div>';
	}

	/**
	 * Enable custom JavaScript display callback
	 */
	public function enable_custom_js_display_callback() {
		$options = get_option( 'aoi_options', array() );
		$value = isset( $options['enable_custom_js_display'] ) ? $options['enable_custom_js_display'] : '0';
		?>
		<input type="checkbox" id="enable_custom_js_display" name="aoi_options[enable_custom_js_display]" value="1" <?php checked( '1', $value ); ?> />
		<label for="enable_custom_js_display"><?php esc_html_e( 'Enable custom JavaScript discount display', 'affiliate-order-integration' ); ?></label>
		<p class="description"><?php esc_html_e( 'When enabled, your custom JavaScript code will be loaded on selected pages with discount data available.', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * Custom JavaScript code callback
	 */
	public function custom_js_code_callback() {
		$options = get_option( 'aoi_options', array() );
		$default_code = '// 🎨 Modern Discount Display Script with Cart Update Detection

		// Validate và display initial discount data
		if (window.aoiDiscountData) {
			// Validate data structure
			const requiredFields = ["hasDiscount", "formattedAmount", "discountPercent"];
			let isValidData = true;
			
			for (let field of requiredFields) {
				if (!(field in window.aoiDiscountData)) {
					isValidData = false;
				}
			}
			
			if (window.aoiDiscountData.hasDiscount && isValidData) {                
				// Create modern floating discount badge
				const discountBadge = document.createElement("div");
				discountBadge.className = "aoi-modern-discount-badge";
				discountBadge.style.cssText = `
					position: fixed;
					top: 80px;
					right: 30px;
					background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
					color: white;
					padding: 20px 25px;
					border-radius: 15px;
					box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
					z-index: 9998;
					max-width: 320px;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					transform: translateX(400px);
					transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
					cursor: pointer;
					border: 2px solid rgba(255,255,255,0.2);
				`;
				
				discountBadge.innerHTML = `
					<div style="display: flex; align-items: center; gap: 15px;">
						<div style="font-size: 32px;">💰</div>
						<div>
							<h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">
								Affiliate Savings!
							</h4>
							<p style="margin: 0; font-size: 14px; opacity: 0.95;">
								Save <strong>${window.aoiDiscountData.formattedAmount}</strong><br>
								<span style="font-size: 12px;">(${window.aoiDiscountData.discountPercent}% discount applied)</span>
							</p>
						</div>
						<div style="margin-left: auto; font-size: 20px; opacity: 0.7;">×</div>
					</div>
				`;
				
				// Add hover effects
				discountBadge.addEventListener("mouseenter", function() {
					this.style.transform = "translateX(0) scale(1.05)";
					this.style.boxShadow = "0 15px 40px rgba(255, 107, 107, 0.4)";
				});
				
				discountBadge.addEventListener("mouseleave", function() {
					this.style.transform = "translateX(0) scale(1)";
					this.style.boxShadow = "0 10px 30px rgba(255, 107, 107, 0.3)";
				});
				
				// Click to close
				discountBadge.addEventListener("click", function() {
					this.style.transform = "translateX(400px)";
					setTimeout(() => this.remove(), 600);
				});
				
				// Insert and animate in
				document.body.appendChild(discountBadge);
				
				// Animate in after small delay
				setTimeout(() => {
					discountBadge.style.transform = "translateX(0)";
				}, 500);
				
				// Auto-hide after 10 seconds
				setTimeout(() => {
					if (document.body.contains(discountBadge)) {
						discountBadge.style.transform = "translateX(400px)";
						setTimeout(() => discountBadge.remove(), 600);
					}
				}, 10000);
			}
		}';


	// Kiểm tra xem đã có options chưa, nếu không thì sử dụng default_code
	$options = get_option( 'aoi_options', array() );
	
	// DEBUG: So sánh với frontend
	error_log( '🔧 AOI ADMIN: Loading options for textarea display' );
	error_log( '🔧 AOI ADMIN: $options keys: ' . implode( ', ', array_keys( $options ) ) );
	error_log( '🔧 AOI ADMIN: isset($options[\'custom_js_code\']): ' . ( isset( $options['custom_js_code'] ) ? 'TRUE' : 'FALSE' ) );
	
	if ( isset( $options['custom_js_code'] ) ) {
		error_log( '🔧 AOI ADMIN: custom_js_code length: ' . strlen( $options['custom_js_code'] ) );
		error_log( '🔧 AOI ADMIN: Contains "Test Badge": ' . ( strpos( $options['custom_js_code'], 'Test Badge' ) !== false ? 'YES' : 'NO' ) );
	}

	// Lấy options và gán default_code sau khi đã khai báo xong
	$value = isset( $options['custom_js_code'] ) ? $options['custom_js_code'] : $default_code;
    ?>
    <textarea id="custom_js_code" name="aoi_options[custom_js_code]" rows="20" class="large-text code" style="font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; font-size: 13px; line-height: 1.4;"><?php echo esc_textarea( $value ); ?></textarea>
    
    <!-- Reset Button -->
    <p style="margin-top: 10px;">
        <button type="button" id="reset_js_code" class="button button-secondary" style="margin-right: 15px;">
            🔄 <?php esc_html_e( 'Reset to Default Code', 'affiliate-order-integration' ); ?>
        </button>
        <span id="reset_status" style="color: #46b450; display: none;">✅ Code reset to default!</span>
    </p>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#reset_js_code').on('click', function() {
            if (confirm('Are you sure you want to reset the JavaScript code to default? This will remove all your custom modifications.')) {
                // Set textarea to default code
                $('#custom_js_code').val(<?php echo wp_json_encode( $default_code ); ?>);
                
                // Show status
                $('#reset_status').show().delay(3000).fadeOut();
                
                // Auto-scroll to top of textarea
                $('#custom_js_code')[0].scrollTop = 0;
                
                // Focus textarea
                $('#custom_js_code').focus();
            }
        });
        
        // Add version timestamp to track changes
        $('#custom_js_code').on('input', function() {
            $(this).attr('data-modified', new Date().getTime());
        });
    });
    </script>
    
    <p class="description">
        <strong><?php esc_html_e( 'Write your custom JavaScript code here.', 'affiliate-order-integration' ); ?></strong><br>
        • <?php esc_html_e( 'Use jQuery if available: ', 'affiliate-order-integration' ); ?><code>jQuery(document).ready(function($) { ... });</code><br>
        • <?php esc_html_e( 'Access discount data via: ', 'affiliate-order-integration' ); ?><code>window.aoiDiscountData</code><br>
        • <?php esc_html_e( 'Code will execute after page load and discount data is available', 'affiliate-order-integration' ); ?><br>
        • <?php esc_html_e( 'Test your code thoroughly on different themes/layouts', 'affiliate-order-integration' ); ?><br>
        • <strong style="color: #d63384;"><?php esc_html_e( 'Click "Reset to Default Code" to restore original badge functionality', 'affiliate-order-integration' ); ?></strong>
    </p>
    <?php
	}

	/**
	 * Custom JavaScript pages callback  
	 */
	public function custom_js_pages_callback() {
		$options = get_option( 'aoi_options', array() );
		$value = isset( $options['custom_js_pages'] ) ? $options['custom_js_pages'] : array( 'checkout', 'thankyou', 'cart' );
		
		$available_pages = array(
			'checkout' => __( 'Checkout Pages', 'affiliate-order-integration' ),
			'thankyou' => __( 'Thank You / Order Received Pages', 'affiliate-order-integration' ), 
			'cart' => __( 'Cart Pages', 'affiliate-order-integration' )
		);
		
		echo '<fieldset>';
		foreach ( $available_pages as $page_key => $page_label ) {
			$checked = in_array( $page_key, (array) $value ) ? 'checked="checked"' : '';
			echo '<label style="display: block; margin: 5px 0;">';
			echo '<input type="checkbox" name="aoi_options[custom_js_pages][]" value="' . esc_attr( $page_key ) . '" ' . $checked . ' />';
			echo ' ' . esc_html( $page_label );
			echo '</label>';
		}
		echo '</fieldset>';
		?>
		<p class="description">
			<?php esc_html_e( 'Select which pages should load your custom JavaScript code.', 'affiliate-order-integration' ); ?><br>
			<strong><?php esc_html_e( 'Recommendation:', 'affiliate-order-integration' ); ?></strong> 
			<?php esc_html_e( 'Enable only on pages where you want to display discounts to optimize performance.', 'affiliate-order-integration' ); ?>
		</p>
		<?php
	}
}