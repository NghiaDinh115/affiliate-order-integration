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
	}

	/**
	 * Thêm menu admin
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Affiliate Order Integration', 'affiliate-order-integration' ),
			__( 'Affiliate Orders', 'affiliate-order-integration' ),
			'manage_options',
			'affiliate-order-integration',
			array( $this, 'settings_page' )
		);

		add_submenu_page(
			'woocommerce',
			__( 'Affiliate Order Logs', 'affiliate-order-integration' ),
			__( 'Affiliate Logs', 'affiliate-order-integration' ),
			'manage_options',
			'affiliate-order-logs',
			array( $this, 'logs_page' )
		);
	}

	/**
	 * Đăng ký settings
	 */
	public function register_settings() {
		register_setting( 'aoi_settings', 'aoi_options' );
		register_setting( 'aoi_settings', 'aff_app_key' );

		add_settings_section(
			'aoi_general_section',
			__( 'Sellmate Affiliate Settings', 'affiliate-order-integration' ),
			array( $this, 'general_section_callback' ),
			'aoi_settings'
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
		$value = isset( $options['partner_id'] ) ? $options['partner_id'] : '1';
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
		$value = isset( $options['auto_send_orders'] ) ? $options['auto_send_orders'] : '1';
		?>
		<input type="checkbox" id="auto_send_orders" name="aoi_options[auto_send_orders]" value="1" <?php checked( 1, $value ); ?> />
		<label for="auto_send_orders"><?php esc_html_e( 'Automatically send orders to affiliate', 'affiliate-order-integration' ); ?></label>
		<?php
	}

	/**
	 * Order status callback
	 */
	public function order_status_callback() {
		$options = get_option( 'aoi_options', array() );
		$value = isset( $options['order_status'] ) ? $options['order_status'] : 'completed';
		?>
		<select id="order_status" name="aoi_options[order_status]">
			<option value="processing" <?php selected( 'processing', $value ); ?>><?php esc_html_e( 'Processing', 'affiliate-order-integration' ); ?></option>
			<option value="completed" <?php selected( 'completed', $value ); ?>><?php esc_html_e( 'Completed', 'affiliate-order-integration' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Select when to send orders to affiliate.', 'affiliate-order-integration' ); ?></p>
		<?php
	}

	/**
	 * Settings page
	 */
	public function settings_page() {
		?>
		<div class="wrap aoi-admin-wrap">
			<h1><?php esc_html_e( 'Affiliate Order Integration Settings', 'affiliate-order-integration' ); ?></h1>
			
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
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_affiliate-order-integration' === $hook || 'woocommerce_page_affiliate-order-logs' === $hook ) {
			wp_enqueue_style( 'aoi-admin', AOI_PLUGIN_URL . 'admin/css/admin.css', array(), AOI_PLUGIN_VERSION );
			wp_enqueue_script( 'aoi-admin', AOI_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), AOI_PLUGIN_VERSION, true );
			wp_localize_script( 'aoi-admin', 'aoi_ajax', array(
				'url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'aoi_ajax_nonce' ),
			) );
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

		$api = new AOI_Affiliate_API();
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
		$result = $handler->send_order_to_affiliate( $order_id );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Order resent successfully', 'affiliate-order-integration' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to resend order', 'affiliate-order-integration' ) ) );
		}
	}
}