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
		// Khởi tạo AOI_Affiliate_API để tự động hook vào thankyou
		$api = new AOI_Affiliate_API();
		
		// Backup hooks cho order status changes (nếu cần)
		add_action( 'woocommerce_order_status_completed', array( $this, 'send_order_to_affiliate' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_send_order_to_affiliate' ) );
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
		$order = wc_get_order( $order_id );
		
		if ( ! $order ) {
			return false;
		}

		// Kiểm tra xem order đã được gửi chưa
		if ( $this->is_order_sent( $order_id ) ) {
			return false;
		}

		// Gửi đến affiliate API
		$api = new AOI_Affiliate_API();
		$result = $api->send_order_to_aff( $order );
		
		// Lưu kết quả vào database
		$this->save_order_log( $order_id, $result );
		
		return $result['success'];
	}

	/**
	 * Chuẩn bị order data để gửi
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array
	 */
	private function prepare_order_data( $order ) {
		$items = array();
		
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = array(
				'name'     => $item->get_name(),
				'sku'      => $product ? $product->get_sku() : '',
				'quantity' => $item->get_quantity(),
				'price'    => $item->get_total(),
			);
		}

		return array(
			'order_id'       => $order->get_id(),
			'order_number'   => $order->get_order_number(),
			'total'          => $order->get_total(),
			'currency'       => $order->get_currency(),
			'customer_email' => $order->get_billing_email(),
			'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'items'          => $items,
			'order_date'     => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
		);
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
		
		$options = get_option( 'aoi_options', array() );
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
}
