<?php
/**
 * Xử lý phần frontend của plugin
 *
 * @package MySamplePlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MSP_Frontend
 * Xử lý tất cả chức năng frontend của plugin
 */
class MSP_Frontend {

	/**
	 * Instance duy nhất của class
	 *
	 * @var MSP_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return MSP_Frontend
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
	 * Khởi tạo các hooks cho frontend
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'wp_head', array( $this, 'add_custom_styles' ) );
		add_filter( 'the_content', array( $this, 'add_content_filter' ) );
	}

	/**
	 * Enqueue frontend scripts và styles
	 */
	public function enqueue_frontend_scripts() {
		wp_enqueue_style(
			'msp-frontend-style',
			MSP_PLUGIN_URL . 'public/css/frontend-style.css',
			array(),
			MSP_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'msp-frontend-script',
			MSP_PLUGIN_URL . 'public/js/frontend-script.js',
			array( 'jquery' ),
			MSP_PLUGIN_VERSION,
			true
		);

		// Localize script để truyền data từ PHP sang JS.
		wp_localize_script(
			'msp-frontend-script',
			'msp_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'msp_nonce' ),
			)
		);
	}

	/**
	 * Thêm custom styles vào head
	 */
	public function add_custom_styles() {
		$options = get_option( 'msp_options' );
		if ( isset( $options['enable_feature_1'] ) && $options['enable_feature_1'] ) {
			?>
			<style>
				.msp-shortcode-container {
					border: 2px solid #0073aa;
					padding: 15px;
					margin: 10px 0;
					border-radius: 5px;
				}
				.msp-type-info {
					background-color: #e7f3ff;
				}
				.msp-type-warning {
					background-color: #fff3cd;
					border-color: #ffc107;
				}
			</style>
			<?php
		}
	}

	/**
	 * Filter để thêm content vào post
	 *
	 * @param string $content Nội dung gốc của post.
	 * @return string
	 */
	public function add_content_filter( $content ) {
		$options = get_option( 'msp_options' );

		if ( isset( $options['enable_feature_1'] ) && $options['enable_feature_1'] && is_single() ) {
			$custom_content  = '<div class="msp-custom-content">';
			$custom_content .= '<p><strong>' . esc_html__( 'Lưu ý:', 'my-sample-plugin' ) . '</strong> ';
			$custom_content .= esc_html( $options['custom_message'] ) . '</p>';
			$custom_content .= '</div>';

			$content = $content . $custom_content;
		}

		return $content;
	}
}