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

		// Thêm cột affiliate status vào danh sách đơn hàng WooCommerce (tương thích với COT và legacy)
		$this->init_order_columns();

		// Thêm meta box cho order edit page (tương thích với COT và legacy)
		$this->init_order_meta_box();
	}

	/**
	 * Khởi tạo order columns tương thích với COT và legacy
	 */
	private function init_order_columns() {
		// Multiple hooks để đảm bảo tương thích với tất cả phiên bản WooCommerce
		
		// Legacy post-based orders
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_affiliate_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'affiliate_column_content' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'affiliate_column_sortable' ) );
		
		// New HPOS (High-Performance Order Storage) hooks
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_affiliate_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'affiliate_column_content_hpos' ), 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', array( $this, 'affiliate_column_sortable' ) );
	}

	/**
	 * Khởi tạo order meta box tương thích với COT và legacy
	 */
	private function init_order_meta_box() {
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
		<input type="checkbox" id="auto_send_orders" name="aoi_options[auto_send_orders]" value="1" <?php checked( 1, $value ); ?> />
		<label for="auto_send_orders"><?php esc_html_e( 'Automatically send orders to affiliate', 'affiliate-order-integration' ); ?></label>
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
		global $post_type;

		// Load on settings page, logs page, and order list/edit pages
		if ( 'settings_page_affiliate-order-integration' === $hook ||
			'woocommerce_page_affiliate-order-logs' === $hook ||
			'shop_order' === $post_type ||
			'edit.php' === $hook && 'shop_order' === $post_type ) {
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
	 * Hiển thị nội dung cột affiliate status cho COT
	 */
	public function affiliate_column_content_cot( $column_name, $order ) {
		if ( 'affiliate_status' !== $column_name ) {
			return;
		}

		$order_id = is_object( $order ) ? $order->get_id() : $order;
		$this->display_affiliate_status( $order_id );
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

		// Lấy CTV token từ order meta
		$ctv_token = get_post_meta( $order_id, '_aoi_ctv_token', true );

		echo 'div class ="aoi-affiliate-info">';

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
			} else {
				echo '<span style="color: #d63638;">✗ ' . esc_html__( 'Send failed', 'affiliate-order-integration' ) . '</span>';
				echo '<br><small>' . esc_html__( 'Last attempt:', 'affiliate-order-integration' ) . ' ' . esc_html( $log->sent_at ) . '</small>';
			}

			// Response data
			if ( $log->response_data ) {
				$response_data = json_decode( $log->response_data, true );
				echo '<br><details style="margin-top: 10px;"><summary>' . esc_html__( 'Response Details', 'affiliate-order-integration' ) . '</summary>';
				echo '<pre style="background: #f5f5f5; padding: 10px; font-size: 11px; max-height: 200px; overflow-y: auto;">' . esc_html( wp_json_encode( $response_data, JSON_PRETTY_PRINT ) ) . '</pre>';
				echo '</details>';
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
}