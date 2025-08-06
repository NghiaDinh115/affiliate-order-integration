<?php
/**
 * Affiliate API Class
 * Xử lý việc gửi requests đến affiliate network
 *
 * @package AffiliateOrderIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AOI_Affiliate_API
 * Xử lý API calls đến affiliate network Sellmate
 */
class AOI_Affiliate_API {

	/**
	 * Plugin options
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Partner ID trong hệ thống affiliate
	 *
	 * @var int
	 */
	private $partner_id;

	/**
	 * Affiliate API URL
	 *
	 * @var string
	 */
	private $api_url = 'http://dev-aff-api.sellmate.cloud/api/v1/partnerSystem/orderCreate';

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->options    = get_option( 'aoi_options', array() );
		$this->partner_id = isset( $this->options['partner_id'] ) ? intval( $this->options['partner_id'] ) : 1;
		$this->log_file   = WP_CONTENT_DIR . '/logs/aff-sellmate.log';
		$this->init_hooks();
	}

	/**
	 * Khởi tạo hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'set_ctv_cookie' ) );
		// NOTE: woocommerce_thankyou hook removed - now handled by AOI_Order_Handler with dynamic status
		// add_action( 'woocommerce_thankyou', array( $this, 'send_order_to_aff_hook' ) );
	}

	/**
	 * Hook vào sự kiện thankyou của WooCommerce
	 *
	 * @param int $order_id Order ID.
	 */
	public function send_order_to_aff_hook( $order_id ) {
		$this->log_message( "THANKYOU HOOK TRIGGERED for Order ID: $order_id" );
		
		$order = wc_get_order( $order_id );
		if ( $order ) {
			// Lưu CTV Token vào order meta để tracking
			$ctv_value = $this->get_ctv_cookie();
			$this->log_message( "CTV Cookie value: " . ($ctv_value ? $ctv_value : 'NULL') );
			
			if ( $ctv_value ) {
				update_post_meta( $order_id, '_aoi_ctv_token', $ctv_value );
				$this->log_message( "CTV Token saved to order meta: $ctv_value" );
			} else {
				$this->log_message( "NO CTV Token found - will not save to order meta" );
			}
			$this->send_order_to_aff( $order );
		}
	}

	/**
	 * Hàm chính để gửi đơn hàng đến affiliate system
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array
	 */
	public function send_order_to_aff( $order ) {
		// Tạo thư mục logs nếu chưa tồn tại
		$log_dir = dirname( $this->log_file );
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		// Kiểm tra nếu cần lấy giá trị cookie
		$ctv_value = $this->get_ctv_cookie();
		if ( empty( $ctv_value ) ) {
			$this->log_message( 'không tìm thấy token!' );
			return array(
				'success' => false,
				'message' => __( 'CTV token not found', 'affiliate-order-integration' ),
			);
		}

		$ctv_data = $this->verify_ctv_token( $ctv_value );
		if ( ! $ctv_data ) {
			$this->log_message( 'token không hợp lệ!' );
			return array(
				'success' => false,
				'message' => __( 'Invalid CTV token', 'affiliate-order-integration' ),
			);
		}

		$ctv_id      = $ctv_data['id'];
		$ctv_link_id = $ctv_data['linkId'] ?? 0;

		// Lấy danh sách sản phẩm từ đơn hàng
		$cuor_products = array();

		foreach ( $order->get_items() as $item ) {
			$item_data = $item->get_data();
			$product   = wc_get_product( $item_data['product_id'] );

			$cuor_products[] = array(
				'name'     => $item_data['name'],
				'quantity' => $item_data['quantity'],
				'price'    => (string) $item_data['total'], // Chuyển về string để đúng định dạng
				'link'     => $product ? $product->get_permalink() : '',
				'sku'      => $product ? $product->get_sku() : '',
			);
		}

		// Dữ liệu JSON gửi đi
		$data = array(
			'cuor_product'      => $cuor_products,
			'cuor_affiliate_id' => $ctv_id,
			'cuor_customer_id'  => $this->partner_id,
			'link_id'           => $ctv_link_id,
		);

		// Log request data
		$this->log_message( 'Request data: ' . wp_json_encode( $data ) );

		// Gửi request
		$result = $this->send_curl_request( $data, $order->get_id() );

		// Nếu gửi thành công, áp dụng discount
		if ( $result['success'] && isset( $result['data'] ) ) {
			$affiliate_order_id = is_array( $result['data'] ) && isset( $result['data']['id'] ) ? $result['data']['id'] : ( is_numeric( $result['data'] ) ? $result['data'] : null );

			if ( $affiliate_order_id ) {
				$discount_amount = $this->get_affiliate_discount( $affiliate_order_id);
				if ( $discount_amount ) {
					$this->apply_affiliate_discount( $order, $discount_amount );
				}
			}
		}
		
		return $result;
	}

	/**
	 * Get discounted price for a product from affiliate API
	 * 
	 * @param int $order_id Order ID từ affiliate system.
 	 * @return float|null
	 */
	public function get_affiliate_discount( $order_id ) {
		$api_url = 'https://aff-api.sellmate.vn/api/v1/partnerSystem/getDiscountByOrderId/' . $order_id;

		if ( empty( $order_id ) ) {
			return null;
		}

		$args = array(
			'headers' => array(
				'Accept'  => 'application/json',
			),
			'timeout' => 10,
		);

		$response = wp_remote_get( $api_url, $args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) ) {
			$this->log_message( 'Discount API error: ' . $response->get_error_message() );
			return null;
		}

		if ( 200 !== $http_code ) {
			$this->log_message( 'Discount API returned HTTP code: ' . $http_code );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log_message( 'Discount JSON decode error: ' . json_last_error_msg() );
			return null;
		}

		if ( isset( $result['data'] ) && is_numeric( $result['data'] ) ) {
			$this->log_message( 'Discount retrieved: ' . $result['data'] );
			return (float) $result['data'];
		}

		$this->log_message( 'Invalid discount response: ' . print_r( $result, true ) );
		return null;
	}

	/**
	 * Apply discount to order
	 * @param WC_Order $order Order object.
 	 * @param float $discount_amount Discount amount.
	 */
	public function apply_affiliate_discount( $order, $discount_amount ) {
		if ( ! is_numeric( $discount_amount ) || $discount_amount <= 0 ) {
			return;
		}

		// Kiểm tra xem đã có discount chưa
		$has_discount = false;
		foreach ( $order->get_fees() as $fee ) {
			if ( $fee->get_name() === 'Affiliate Discount' ) {
				$has_discount = true;
				break;
			}
		}

		if ( ! $has_discount ) {
			// Kiểm tra WooCommerce class tồn tại
			if ( ! class_exists( 'WC_Order_Item_Fee' ) ) {
				$this->log_message( 'WC_Order_Item_Fee class not found' );
				return;
			}
			
			// Thêm phí Affiliate Discount mới
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( 'Affiliate Discount' );
			$fee->set_amount( -$discount_amount );
			$fee->set_total( -$discount_amount );
			$fee->set_tax_class( '' );
			$fee->set_tax_status( 'none' );
			$order->add_item( $fee );

			// Cập nhật tổng tiền của đơn hàng
			$order->calculate_totals();
			$order->save();

			$this->log_message( 'Affiliate discount applied: ' . $discount_amount );
		}
	}

	/**
	 * Gửi CURL request đến affiliate API
	 *
	 * @param array $data Data to send.
	 * @param int $order_id Order ID for logging.
	 * @return array
	 */
	private function send_curl_request( $data, $order_id = null ) {
		$json_data = wp_json_encode( $data );

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => $json_data,
			'timeout' => 30,
		);

		$response = wp_remote_request( $this->api_url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->log_message( 'WordPress HTTP Error: ' . $error_message );
			return array(
				'success' => false,
				'message' => $error_message,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$this->log_message( 'HTTP Code: ' . $response_code . ' Response: ' . $response_body );

		// Lưu vào database để hiển thị status
		if ( $order_id ) {
			$this->save_to_database( $order_id, $response_code, $response_body );
		}

		if ( 200 === $response_code || 201 === $response_code ) {
			return array(
				'success' => true,
				'message' => __( 'Order sent successfully to affiliate', 'affiliate-order-integration' ),
				'data'    => json_decode( $response_body, true ),
			);
		} else {
			return array(
				'success' => false,
				'message' => sprintf( __( 'API returned status code: %d', 'affiliate-order-integration' ), $response_code ),
				'data'    => $response_body,
			);
		}
	}

	/**
	 * Test connection đến affiliate API
	 *
	 * @return array
	 */
	public function test_connection() {
		// Tạo test data
		$test_data = array(
			'cuor_product'      => array(
				array(
					'name'     => 'Test Product',
					'quantity' => 1,
					'price'    => '100000',
					'link'     => 'https://example.com/test-product',
					'sku'      => 'TEST-001',
				),
			),
			'cuor_affiliate_id' => 999999,
			'cuor_customer_id'  => $this->partner_id,
			'link_id'           => 0,
		);

		$this->log_message( 'Testing connection with data: ' . wp_json_encode( $test_data ) );

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode( $test_data ),
			'timeout' => 15,
		);

		$response = wp_remote_request( $this->api_url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->log_message( 'Test connection failed: ' . $error_message );
			return array(
				'success' => false,
				'message' => $error_message,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$this->log_message( 'Test connection response code: ' . $response_code );
		$this->log_message( 'Test connection response body: ' . $response_body );

		if ( 200 === $response_code || 201 === $response_code ) {
			return array(
				'success' => true,
				'message' => __( 'Connection to Sellmate API successful', 'affiliate-order-integration' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Connection failed with status code: %d', 'affiliate-order-integration' ), $response_code ),
			);
		}
	}

	/**
	 * Hàm lưu giá trị ctv vào cookie trong 48 giờ
	 */
	public function set_ctv_cookie() {
		if ( ! empty( $_GET['ctv'] ) ) {
			$ctv_value = sanitize_text_field( $_GET['ctv'] );
			setcookie( 'ctv', $ctv_value, time() + ( 48 * 3600 ), '/' ); // 48 giờ
			$this->log_message( 'CTV cookie set: ' . $ctv_value );
		}
	}

	/**
	 * Hàm lấy giá trị ctv từ cookie
	 *
	 * @return string|null
	 */
	public function get_ctv_cookie() {
		return isset( $_COOKIE['ctv'] ) ? sanitize_text_field( $_COOKIE['ctv'] ) : null;
	}

	/**
	 * Xác thực token được ký theo app key của user
	 *
	 * @param string $token Token to verify.
	 * @return array|null
	 */
	public function verify_ctv_token( $token ) {
		$decoded = base64_decode( $token );
		if ( ! $decoded ) {
			return null;
		}

		$parts = explode( '.', $decoded );
		if ( count( $parts ) !== 2 ) {
			return null;
		}

		list( $payload, $signature ) = $parts;

		// Uncomment và cấu hình APP_KEY nếu cần xác thực signature
		$app_key = get_option( 'aff_app_key' );
		if ( ! empty( $app_key ) && $signature !== md5( $payload . $app_key ) ) {
			return null;
		}

		$data = json_decode( $payload, true );
		if ( ! $data || ! isset( $data['id'] ) ) {
			return null;
		}

		return array(
			'id'     => $data['id'],
			'linkId' => $data['linkId'] ?? 0,
		);
	}

	/**
	 * Log message to file
	 *
	 * @param string $message Message to log.
	 */
	private function log_message( $message ) {
		$timestamp = date( 'Y-m-d H:i:s' );
		$log_entry = '[' . $timestamp . '] ' . $message . PHP_EOL;
		file_put_contents( $this->log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Lưu kết quả API call vào database
	 *
	 * @param int $order_id Order ID.
	 * @param int $response_code HTTP response code.
	 * @param string $response_body Response body.
	 */
	private function save_to_database( $order_id, $response_code, $response_body ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aoi_affiliate_orders';

		$status = ( 200 === $response_code || 201 === $response_code ) ? 'sent' : 'failed';

		$wpdb->replace(
			$table_name,
			array(
				'order_id'      => $order_id,
				'status'        => $status,
				'affiliate_url' => $this->api_url,
				'response_data' => $response_body,
				'sent_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		$this->log_message( "Database saved: Order $order_id - Status: $status" );
	}

	/**
	 * Activate plugin - tạo thư mục logs
	 */
	public static function activate() {
		// Check if WordPress constants are available
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return; // Skip if WP not fully loaded
		}

		$log_dir = WP_CONTENT_DIR . '/logs';
		
		// Use PHP mkdir instead of wp_mkdir_p to avoid dependency
		if ( ! file_exists( $log_dir ) ) {
			if ( function_exists( 'wp_mkdir_p' ) ) {
				wp_mkdir_p( $log_dir );
			} else {
				// Fallback to PHP mkdir
				@mkdir( $log_dir, 0755, true );
			}
		}
	}
}
