<?php
/**
 * Xử lý phần admin của plugin
 *
 * @package MySamplePlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MSP_Admin
 * Xử lý tất cả chức năng admin của plugin
 */
class MSP_Admin {

	/**
	 * Instance duy nhất của class
	 *
	 * @var MSP_Admin|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return MSP_Admin
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
	}

	/**
	 * Thêm menu admin
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'My Sample Plugin', 'my-sample-plugin' ),
			__( 'MSP Settings', 'my-sample-plugin' ),
			'manage_options',
			'msp-settings',
			array( $this, 'settings_page' ),
			'dashicons-admin-plugins',
			30
		);
	}

	/**
	 * Đăng ký settings
	 */
	public function register_settings() {
		register_setting( 'msp_settings_group', 'msp_options' );

		add_settings_section(
			'msp_general_section',
			__( 'Cài đặt chung', 'my-sample-plugin' ),
			array( $this, 'general_section_callback' ),
			'msp-settings'
		);

		add_settings_field(
			'custom_message',
			__( 'Tin nhắn tùy chỉnh', 'my-sample-plugin' ),
			array( $this, 'custom_message_callback' ),
			'msp-settings',
			'msp_general_section'
		);

		add_settings_field(
			'enable_feature_1',
			__( 'Bật tính năng 1', 'my-sample-plugin' ),
			array( $this, 'enable_feature_1_callback' ),
			'msp-settings',
			'msp_general_section'
		);
	}

	/**
	 * Callback cho section
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Cấu hình các tùy chọn chung cho plugin.', 'my-sample-plugin' ) . '</p>';
	}

	/**
	 * Callback cho custom message field
	 */
	public function custom_message_callback() {
		$options = get_option( 'msp_options' );
		$value   = isset( $options['custom_message'] ) ? $options['custom_message'] : '';
		echo '<input type="text" name="msp_options[custom_message]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Callback cho enable feature 1 field
	 */
	public function enable_feature_1_callback() {
		$options = get_option( 'msp_options' );
		$checked = isset( $options['enable_feature_1'] ) && $options['enable_feature_1'] ? 'checked' : '';
		echo '<input type="checkbox" name="msp_options[enable_feature_1]" value="1" ' . esc_attr( $checked ) . ' />';
		echo '<label>' . esc_html__( 'Bật tính năng này', 'my-sample-plugin' ) . '</label>';
	}

	/**
	 * Trang settings
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'msp_settings_group' );
				do_settings_sections( 'msp-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts và styles
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_msp-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'msp-admin-style',
			MSP_PLUGIN_URL . 'admin/css/admin-style.css',
			array(),
			MSP_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'msp-admin-script',
			MSP_PLUGIN_URL . 'admin/js/admin-script.js',
			array( 'jquery' ),
			MSP_PLUGIN_VERSION,
			true
		);
	}
}