<?php
/**
 * Main class for the My Sample Plugins
 *
 * @package MySamplePlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class MSP_Core
 * Core functionality của plugin
 *
 * @package MySamplePlugin
 */
class MSP_Core {

	/**
	 * Instance duy nhất của class
	 *
	 * @var MSP_Core|null
	 */
	private static $instance = null;

	/**
	 * Lấy instance của class (Singleton pattern)
	 *
	 * @return MSP_Core
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
	 * Khởi tạo các hooks cho core functionality
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Register shortcode.
		add_shortcode( 'msp_display', array( $this, 'display_shortcode' ) );
		// Register custom post type.
		add_action( 'init', array( $this, 'register_post_type' ) );
		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Shortcode to display content
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function display_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'message' => 'Default message',
				'type'    => 'info',
			),
			$atts
		);

		$options = get_option( 'msp_options' );

		ob_start();
		?>
		<div class="msp-shortcode-container msp-type-<?php echo esc_attr( $atts['type'] ); ?>">
			<h3><?php echo esc_html( $options['custom_message'] ); ?></h3>
			<p><?php echo esc_html( $atts['message'] ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register custom post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		$args = array(
			'labels'       => array(
				'name'          => __( 'MSP Items', 'my-sample-plugin' ),
				'singular_name' => __( 'MSP Item', 'my-sample-plugin' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-admin-plugins',
			'show_in_rest' => true,
		);
		register_post_type( 'msp_custom_post', $args );
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'msp/v1',
			'/items',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Callback for REST API route to get items
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$posts = get_posts(
			array(
				'post_type'   => 'msp_item',
				'numberposts' => 10,
			)
		);

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'content' => apply_filters( 'the_content', $post->post_content ),
			);
		}
		return new WP_REST_Response( $items );
	}
}
