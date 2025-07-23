<?php
/**
 * PHPUnit bootstrap file
 *
 * @package MySamplePlugin
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// WordPress test environment (if available)
if (file_exists('/tmp/wordpress-tests-lib/includes/functions.php')) {
    require_once '/tmp/wordpress-tests-lib/includes/functions.php';
    
    function _manually_load_plugin() {
        require dirname(__DIR__) . '/my-sample-plugin.php';
    }
    
    // Check if tests_add_filter function exists
    if (function_exists('tests_add_filter')) {
        tests_add_filter('muplugins_loaded', '_manually_load_plugin');
    }
    
    require '/tmp/wordpress-tests-lib/includes/bootstrap.php';
} else {
    // Mock WordPress functions for unit tests
    if (!function_exists('tests_add_filter')) {
        function tests_add_filter($hook, $callback) {
            // Mock implementation for testing without WordPress test suite
            return true;
        }
    }
    
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
            // Mock implementation
        }
    }
    
    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            // Mock implementation
        }
    }
    
    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            // Mock implementation
        }
    }
    
    if (!function_exists('plugin_dir_url')) {
        function plugin_dir_url($file) {
            return 'http://example.com/wp-content/plugins/my-sample-plugin/';
        }
    }
    
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) {
            return dirname(__DIR__) . '/';
        }
    }
    
    if (!function_exists('plugin_basename')) {
        function plugin_basename($file) {
            return 'my-sample-plugin/my-sample-plugin.php';
        }
    }
    
    if (!function_exists('load_plugin_textdomain')) {
        function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
            return true;
        }
    }
    
    if (!function_exists('is_admin')) {
        function is_admin() {
            return false;
        }
    }
    
    if (!function_exists('flush_rewrite_rules')) {
        function flush_rewrite_rules($hard = true) {
            // Mock implementation
        }
    }
    
    if (!function_exists('add_option')) {
        function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
            return true;
        }
    }
    
    if (!function_exists('dbDelta')) {
        function dbDelta($queries = '', $execute = true) {
            return array();
        }
    }
    
    // Mock additional WordPress functions for plugin compatibility
    if (!function_exists('add_shortcode')) {
        function add_shortcode($tag, $callback) {
            return true;
        }
    }
    
    if (!function_exists('shortcode_atts')) {
        function shortcode_atts($pairs, $atts, $shortcode = '') {
            return array_merge($pairs, (array) $atts);
        }
    }
    
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) {
            return $default;
        }
    }
    
    if (!function_exists('esc_attr')) {
        function esc_attr($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    
    if (!function_exists('esc_html')) {
        function esc_html($text) {
            return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        }
    }
    
    if (!function_exists('__')) {
        function __($text, $domain = 'default') {
            return $text;
        }
    }
    
    if (!function_exists('register_post_type')) {
        function register_post_type($post_type, $args = array()) {
            return true;
        }
    }
    
    if (!function_exists('register_rest_route')) {
        function register_rest_route($namespace, $route, $args = array()) {
            return true;
        }
    }
    
    if (!function_exists('get_posts')) {
        function get_posts($args = array()) {
            return array();
        }
    }
    
    if (!function_exists('apply_filters')) {
        function apply_filters($tag, $value) {
            return $value;
        }
    }
    
    if (!class_exists('WP_REST_Request')) {
        class WP_REST_Request {
            public function __construct($method = '', $route = '', $attributes = array()) {}
        }
    }
    
    if (!class_exists('WP_REST_Response')) {
        class WP_REST_Response {
            public $data;
            public function __construct($data = null, $status = 200, $headers = array()) {
                $this->data = $data;
            }
        }
    }
    
    if (!function_exists('wp_enqueue_style')) {
        function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
            return true;
        }
    }
    
    if (!function_exists('wp_enqueue_script')) {
        function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
            return true;
        }
    }
    
    if (!function_exists('add_menu_page')) {
        function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {
            return true;
        }
    }
    
    if (!function_exists('add_settings_section')) {
        function add_settings_section($id, $title, $callback, $page) {
            return true;
        }
    }
    
    if (!function_exists('add_settings_field')) {
        function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array()) {
            return true;
        }
    }
    
    if (!function_exists('register_setting')) {
        function register_setting($option_group, $option_name, $sanitize_callback = '') {
            return true;
        }
    }
    
    if (!function_exists('settings_fields')) {
        function settings_fields($option_group) {
            return true;
        }
    }
    
    if (!function_exists('do_settings_sections')) {
        function do_settings_sections($page) {
            return true;
        }
    }
    
    if (!function_exists('submit_button')) {
        function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {
            return '<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">';
        }
    }
    
    if (!function_exists('get_admin_page_title')) {
        function get_admin_page_title() {
            return 'My Sample Plugin Settings';
        }
    }
    
    if (!function_exists('wp_add_inline_style')) {
        function wp_add_inline_style($handle, $data) {
            return true;
        }
    }
    
    if (!function_exists('wp_localize_script')) {
        function wp_localize_script($handle, $object_name, $l10n) {
            return true;
        }
    }
    
    // Mock global $wpdb object
    if (!isset($GLOBALS['wpdb'])) {
        $GLOBALS['wpdb'] = new stdClass();
        $GLOBALS['wpdb']->prefix = 'wp_';
        $GLOBALS['wpdb']->get_charset_collate = function() {
            return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        };
    }
    
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
}
