<?php
/**
 * Debug Hook Test - Add to wp-content/mu-plugins/
 * hoặc thêm vào functions.php để test hooks
 */

// Test basic column hooks
add_action('wp_loaded', function() {
    if (!is_admin()) return;
    
    // Test tất cả hooks có thể
    $hooks_to_test = [
        'manage_edit-shop_order_columns',
        'manage_shop_order_posts_custom_column', 
        'manage_woocommerce_page_wc-orders_columns',
        'manage_woocommerce_page_wc-orders_custom_column'
    ];
    
    foreach ($hooks_to_test as $hook) {
        add_filter($hook, function($columns) use ($hook) {
            error_log("AOI DEBUG: Hook $hook fired with columns: " . print_r(array_keys($columns), true));
            
            // Force add our column
            $columns['aoi_debug_status'] = 'AOI Debug Status';
            return $columns;
        }, 999);
    }
    
    // Test column content
    add_action('manage_shop_order_posts_custom_column', function($column, $order_id) {
        if ($column === 'aoi_debug_status') {
            echo '<span style="color: green;">✓ AOI Legacy Hook Works!</span>';
            error_log("AOI DEBUG: Legacy column content hook fired for order $order_id");
        }
    }, 10, 2);
    
    add_action('manage_woocommerce_page_wc-orders_custom_column', function($column, $order) {
        if ($column === 'aoi_debug_status') {
            $order_id = is_object($order) ? $order->get_id() : $order;
            echo '<span style="color: blue;">✓ AOI HPOS Hook Works!</span>';
            error_log("AOI DEBUG: HPOS column content hook fired for order $order_id");
        }
    }, 10, 2);
});

// Test meta box
add_action('add_meta_boxes', function() {
    error_log("AOI DEBUG: add_meta_boxes hook fired");
    
    add_meta_box(
        'aoi_debug_meta',
        'AOI Debug Meta Box',
        function($post) {
            echo '<p>AOI Debug Meta Box is working! Order ID: ' . $post->ID . '</p>';
            error_log("AOI DEBUG: Meta box displayed for order " . $post->ID);
        },
        ['shop_order', 'woocommerce_page_wc-orders'],
        'side'
    );
}, 999);

// Test admin notices để xem current hook
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    
    global $hook_suffix, $post_type;
    if (strpos($_SERVER['REQUEST_URI'], 'wc-orders') !== false || 
        strpos($_SERVER['REQUEST_URI'], 'edit.php') !== false ||
        $post_type === 'shop_order') {
        
        echo '<div class="notice notice-info"><p>';
        echo '<strong>AOI Hook Debug:</strong> ';
        echo 'Current Hook: ' . ($hook_suffix ?? 'none') . ' | ';
        echo 'Post Type: ' . ($post_type ?? 'none') . ' | ';
        echo 'URL: ' . $_SERVER['REQUEST_URI'];
        echo '</p></div>';
    }
});

// Force WooCommerce hooks nếu chưa có
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        error_log("AOI DEBUG: WooCommerce is loaded");
        
        // Check nếu HPOS enabled
        if (function_exists('wc_get_container') && 
            wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()) {
            error_log("AOI DEBUG: HPOS is enabled");
        } else {
            error_log("AOI DEBUG: HPOS is not enabled, using legacy");
        }
    }
});
?>
