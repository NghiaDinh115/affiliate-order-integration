<?php
/**
 * Debug script để kiểm tra trạng thái plugin
 * Chạy script này từ WordPress admin hoặc từ terminal
 */

// Load WordPress environment
if (!defined('ABSPATH')) {
    // Tìm wp-config.php
    $config_file = '';
    $current_dir = __DIR__;
    
    // Tìm trong thư mục cha
    for ($i = 0; $i < 5; $i++) {
        if (file_exists($current_dir . '/wp-config.php')) {
            $config_file = $current_dir . '/wp-config.php';
            break;
        }
        $current_dir = dirname($current_dir);
    }
    
    if (empty($config_file)) {
        echo "Không tìm thấy wp-config.php. Vui lòng đặt file này trong thư mục WordPress.\n";
        exit;
    }
    
    require_once $config_file;
    require_once ABSPATH . 'wp-settings.php';
}

global $wpdb;

echo "=== DEBUG AFFILIATE ORDER INTEGRATION PLUGIN ===\n\n";

// 1. Kiểm tra plugin có active không
$active_plugins = get_option('active_plugins', array());
$is_active = in_array('affiliate-order-integration/affiliate-order-integration.php', $active_plugins);
echo "1. Plugin Active: " . ($is_active ? "YES" : "NO") . "\n";

// 2. Kiểm tra database table
$table_name = $wpdb->prefix . 'aoi_affiliate_orders';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
echo "2. Database Table ($table_name): " . ($table_exists ? "EXISTS" : "NOT EXISTS") . "\n";

if ($table_exists) {
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "   - Records count: $row_count\n";
}

// 3. Kiểm tra options
$aoi_options = get_option('aoi_options', false);
echo "3. Plugin Options: " . ($aoi_options ? "SET" : "NOT SET") . "\n";
if ($aoi_options) {
    echo "   - Partner ID: " . ($aoi_options['partner_id'] ?? 'not set') . "\n";
    echo "   - Auto send: " . ($aoi_options['auto_send_orders'] ?? 'not set') . "\n";
    echo "   - Order status: " . ($aoi_options['order_status'] ?? 'not set') . "\n";
}

$app_key = get_option('aff_app_key', '');
echo "   - App Key: " . (empty($app_key) ? "NOT SET" : "SET (****)") . "\n";

// 4. Kiểm tra WooCommerce
if (class_exists('WooCommerce')) {
    echo "4. WooCommerce: ACTIVE\n";
    
    // Kiểm tra shop_order post type
    $orders_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'");
    echo "   - Total orders: $orders_count\n";
    
    // Kiểm tra orders có CTV token
    $ctv_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_aoi_ctv_token'");
    echo "   - Orders with CTV token: $ctv_orders\n";
    
} else {
    echo "4. WooCommerce: NOT ACTIVE\n";
}

// 5. Kiểm tra hooks đã được đăng ký
echo "5. Admin Hooks Check:\n";
if (class_exists('AOI_Admin')) {
    echo "   - AOI_Admin class: EXISTS\n";
    
    // Kiểm tra các hooks
    global $wp_filter;
    
    $hooks_to_check = array(
        'admin_menu',
        'admin_init', 
        'admin_enqueue_scripts',
        'manage_edit-shop_order_columns',
        'manage_shop_order_posts_custom_column',
        'add_meta_boxes'
    );
    
    foreach ($hooks_to_check as $hook) {
        $registered = isset($wp_filter[$hook]) && !empty($wp_filter[$hook]);
        echo "   - $hook: " . ($registered ? "REGISTERED" : "NOT REGISTERED") . "\n";
    }
} else {
    echo "   - AOI_Admin class: NOT EXISTS\n";
}

// 6. Kiểm tra file tồn tại
$files_to_check = array(
    'includes/class-admin.php',
    'includes/class-affiliate-api.php', 
    'admin/css/admin.css',
    'admin/js/admin.js'
);

echo "6. Plugin Files:\n";
foreach ($files_to_check as $file) {
    $file_path = __DIR__ . '/' . $file;
    echo "   - $file: " . (file_exists($file_path) ? "EXISTS" : "MISSING") . "\n";
}

// 7. Test một WooCommerce order
if (class_exists('WooCommerce') && $orders_count > 0) {
    $latest_order = $wpdb->get_row("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' ORDER BY ID DESC LIMIT 1");
    if ($latest_order) {
        echo "7. Latest Order Test:\n";
        echo "   - Order ID: #{$latest_order->ID}\n";
        
        $ctv_token = get_post_meta($latest_order->ID, '_aoi_ctv_token', true);
        echo "   - CTV Token: " . (empty($ctv_token) ? "NONE" : "EXISTS (" . substr($ctv_token, 0, 20) . "...)") . "\n";
        
        $affiliate_log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $latest_order->ID));
        echo "   - Affiliate Log: " . ($affiliate_log ? "EXISTS ({$affiliate_log->status})" : "NONE") . "\n";
    }
}

echo "\n=== END DEBUG ===\n";

// Nếu chạy từ browser, thêm HTML formatting
if (isset($_SERVER['HTTP_HOST'])) {
    echo '<pre>';
    // Output đã được echo ở trên
    echo '</pre>';
}
?>
