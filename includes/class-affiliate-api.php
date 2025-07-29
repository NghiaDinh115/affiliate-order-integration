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
		add_action( 'woocommerce_thankyou', array( $this, 'send_order_to_aff_hook' ) );
	}

	/**
	 * Hook vào sự kiện thankyou của WooCommerce
	 *
	 * @param int $order_id Order ID.
	 */
	public function send_order_to_aff_hook( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			// Lưu CTV Token vào order meta để tracking
			$ctv_value = $this->get_ctv_cookie();
			if ( $ctv_value ) {
				update_post_meta( $order_id, '_aoi_ctv_token', $ctv_value );
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
		return $this->send_curl_request( $data );
	}

	/**
	 * Gửi CURL request đến affiliate API
	 *
	 * @param array $data Data to send.
	 * @return array
	 */
	private function send_curl_request( $data ) {
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
	 * Activate plugin - tạo thư mục logs
	 */
	public static function activate() {
		$log_dir = WP_CONTENT_DIR . '/logs';
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}
	}
}
