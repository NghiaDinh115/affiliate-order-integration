<?php
/**
 * Order Handler Class
 * Xử lý việc gửi orders đến affiliate network
 *
 * @package AffiliateOrderIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AOI_Order_Handler
 * Xử lý việc gửi orders đến affiliate
 */
class AOI_Order_Handler {

	/**
	 * Instance duy nhất của class
	 *
	 * @var AOI_Order_Handler|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return AOI_Order_Handler
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
	 * Khởi tạo các hooks cho order handling
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Kiểm tra WooCommerce có active không
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice') );
			return; // Không khởi tạo hooks nếu WooCommerce không active
		}

		// Check runtime dependency
		add_action( 'admin_init', array( $this, 'check_woocommerce_dependency' ) );

		// Handle CTV cookie (moved from AOI_Affiliate_API)
		add_action( 'init', array( $this, 'set_ctv_cookie' ) );

		// Dynamic hooks dựa trên setting order_status
		$this->setup_dynamic_hooks();
	}

	/**
	 * Gửi order đến affiliate khi order completed
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function send_order_to_affiliate( $order_id ) {
		$options = get_option( 'aoi_options', array() );

		if ( empty( $options['auto_send_orders'] ) ) {
			return;
		}

		$this->process_order( $order_id );
	}

	/**
	 * Có thể gửi order đến affiliate dựa trên setting
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function maybe_send_order_to_affiliate( $order_id ) {
		$options = get_option( 'aoi_options', array() );

		if ( empty( $options['auto_send_orders'] ) ) {
			return;
		}

		$order_status = isset( $options['order_status'] ) ? $options['order_status'] : 'completed';

		if ( 'processing' === $order_status ) {
			$this->process_order( $order_id );
		}
	}

	/**
	 * Xử lý và gửi order data
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private function process_order( $order_id ) {
		// Double check trước khi gọi WooCommerce functions
		if ( ! function_exists( 'wc_get_order' ) ) {
			error_log( 'AOI: WooCommerce not available when trying to process order ' . $order_id );
			return false;
		}
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Kiểm tra xem order đã được gửi chưa
		if ( $this->is_order_sent( $order_id ) ) {
			return false;
		}

		// Gửi đến affiliate API
		$api    = new AOI_Affiliate_API();
		$result = $api->send_order_to_aff( $order );

		// Lưu kết quả vào database
		$this->save_order_log( $order_id, $result );

		return $result['success'];
	}

	/**
	 * Kiểm tra xem order đã được gửi chưa
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private function is_order_sent( $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE order_id = %d", $order_id ) );

		return null !== $result;
	}

	/**
	 * Lưu log của việc gửi order
	 *
	 * @param int   $order_id Order ID.
	 * @param array $result Result from API.
	 * @return void
	 */
	private function save_order_log( $order_id, $result ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';

		$options       = get_option( 'aoi_options', array() );
		$affiliate_url = isset( $options['affiliate_url'] ) ? $options['affiliate_url'] : '';

		$wpdb->insert(
			$table_name,
			array(
				'order_id'      => $order_id,
				'affiliate_url' => $affiliate_url,
				'status'        => $result['success'] ? 'sent' : 'failed',
				'response_data' => wp_json_encode( $result ),
				'sent_at'       => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Kiểm tra WooCommerce có active không
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_order' );
	}

	/**
	 * Hiển thị thông báo nếu WooCommerce không active
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="notice notice-error"><p>';
		echo '<strong>' . esc_html__( 'Affiliate Order Integration', 'affiliate-order-integration' ) . '</strong> ';
		echo esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'affiliate-order-integration' );
		echo '</p></div>';
	}

	/**
	 * Kiểm tra WooCommerce dependency trong runtime
	 *
	 * @return void
	 */
	public function check_woocommerce_dependency() {
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice') );

			// Optional: Auto-deactivate plugin 
			if ( current_user_can( 'activate_plugins' ) ) {
				deactivate_plugins( plugin_basename( AOI_PLUGIN_FILE ) );
			}
		}
	}

	/**
	 * Setup hooks động dựa trên order status
	 *
	 * @return void
	 */
	private function setup_dynamic_hooks() {
		// Lấy các tùy chọn từ database
		$options = get_option( 'aoi_options', array());
		$order_status = isset( $options['order_status'] ) ? $options['order_status'] : 'completed';

		// Hook vào status được chọn để GỬI
		switch ( $order_status ) {
			case 'processing':
				add_action('woocommerce_order_status_processing', array( $this, 'send_order_to_affiliate' ) );
				break;
			case 'on-hold': 
				add_action('woocommerce_order_status_on-hold', array( $this, 'send_order_to_affiliate' ) );
				break;
			case 'completed':
				add_action('woocommerce_order_status_completed', array( $this, 'send_order_to_affiliate' ) );
				break;
			case 'cancelled':
				add_action('woocommerce_order_status_cancelled', array( $this, 'send_order_to_affiliate' ) );
				break;
			default:
				error_log("AOI DEBUG: Unsupported order status '$order_status' for affiliate sending.");
				break;
		}

		// Hook để xử lý khi status thay đổi KHỎI target status (rollback)
		add_action('woocommerce_order_status_changed', array( $this, 'handle_status_rollback' ), 10, 4);
	}

	/**
	 * Xử lý khi order status rollback (ví dụ: completed → processing)
	 *
	 * @param int $order_id Order ID.
	 * @param string $old_status Old status without 'wc-' prefix.
	 * @param string $new_status New status without 'wc-' prefix.
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function handle_status_rollback( $order_id, $old_status, $new_status, $order ) {
		$options = get_option( 'aoi_options', array());
		$target_status = isset( $options['order_status'] ) ? $options['order_status'] : 'completed';
		
		// Chỉ xử lý nếu auto_send_orders được bật
		if ( empty( $options['auto_send_orders'] ) ) {
			return;
		}

		// Kiểm tra nếu status thay đổi KHỎI target status
		if ( $old_status === $target_status && $new_status !== $target_status ) {
			$this->mark_order_as_rollback( $order_id, $old_status, $new_status );
		}
	}

	/**
	 * Đánh dấu order là rollback trong database
	 *
	 * @param int $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @return void
	 */
	private function mark_order_as_rollback( $order_id, $old_status, $new_status ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';

		// Kiểm tra xem order đã được gửi chưa
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %d", $order_id ) );
		
		if ( $existing && 'sent' === $existing->status ) {
			// Lưu lại response data gốc để không mất thông tin
			$original_response = json_decode( $existing->response_data, true );
			
			// Tạo rollback data mới nhưng giữ thông tin gốc
			$rollback_data = array(
				'rollback' => true,
				'old_status' => $old_status,
				'new_status' => $new_status,
				'rollback_time' => current_time( 'mysql' ),
				'message' => "Order status changed from {$old_status} to {$new_status}",
				'original_response' => $original_response, // Giữ lại response gốc
			);

			// Lưu CTV token vào order meta để không bị mất
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$ctv_token = $order->get_meta( '_aoi_ctv_token' );
				if ( empty( $ctv_token ) ) {
					// Nếu chưa có trong meta, lấy từ cookie hiện tại
					$api = new AOI_Affiliate_API();
					$current_ctv = $api->get_ctv_cookie();
					if ( $current_ctv ) {
						$order->update_meta_data( '_aoi_ctv_token', $current_ctv );
						$order->save();
					}
				}
			}
			
			// Cập nhật status thành 'rollback'
			$wpdb->update(
				$table_name,
				array(
					'status' => 'rollback',
					'response_data' => wp_json_encode( $rollback_data ),
					'sent_at' => current_time( 'mysql' ),
				),
				array( 'order_id' => $order_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			// Log for debugging
			$log_file = WP_CONTENT_DIR . '/logs/aff-sellmate.log';
			$timestamp = date( 'Y-m-d H:i:s' );
			$log_entry = '[' . $timestamp . '] ORDER ROLLBACK: Order #' . $order_id . ' status changed from ' . $old_status . ' to ' . $new_status . ' (affiliate order NOT cancelled - requires manual intervention)' . PHP_EOL;
			file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
		}
	}

	/**
	 * Set CTV cookie (moved from AOI_Affiliate_API)
	 *
	 * @return void
	 */
	public function set_ctv_cookie() {
		if ( ! empty( $_GET['ctv'] ) ) {
			$ctv_value = sanitize_text_field( $_GET['ctv'] );
			setcookie( 'ctv', $ctv_value, time() + ( 48 * 3600 ), '/' ); // 48 giờ
			
			// Log to file for debugging
			$log_file = WP_CONTENT_DIR . '/logs/aff-sellmate.log';
			$log_dir = dirname( $log_file );
			if ( ! file_exists( $log_dir ) ) {
				wp_mkdir_p( $log_dir );
			}
			$timestamp = date( 'Y-m-d H:i:s' );
			$log_entry = '[' . $timestamp . '] CTV cookie set: ' . $ctv_value . PHP_EOL;
			file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
		}
	}
}
