# Hướng dẫn Debug Plugin Affiliate Order Integration

## Vấn đề: Không thấy cột Affiliate Status và Meta Box

### Bước 1: Kiểm tra Plugin đã Active chưa

1. Vào **WordPress Admin > Plugins**
2. Tìm "Affiliate Order Integration" 
3. Đảm bảo có màu xanh "Active"
4. Nếu chưa active, click "Activate"

### Bước 2: Kiểm tra WooCommerce 

1. Đảm bảo **WooCommerce plugin** đã active
2. Vào **WooCommerce > Orders** 
3. Nếu không có menu này, WooCommerce chưa được cài đặt đúng

### Bước 3: Enable Debug Mode

Thêm code này vào `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Bước 4: Test với Debug Code

Thêm code này vào `functions.php` của theme:

```php
// DEBUG: Test AOI Plugin Hooks
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    
    global $hook_suffix, $post_type;
    if (strpos($_SERVER['REQUEST_URI'], 'edit.php') !== false && 
        isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
        
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>AOI Debug:</strong> Đang ở trang Shop Orders ';
        echo '| Hook: ' . ($hook_suffix ?? 'none');
        echo '| Post Type: ' . ($post_type ?? 'none');
        echo '</p></div>';
    }
});

// Force add test column
add_filter('manage_edit-shop_order_columns', function($columns) {
    $columns['test_aoi'] = 'Test AOI Column';
    error_log('AOI DEBUG: Column filter fired');
    return $columns;
}, 999);

add_action('manage_shop_order_posts_custom_column', function($column, $order_id) {
    if ($column === 'test_aoi') {
        echo '<span style="color: green;">✓ Hook Works!</span>';
        error_log('AOI DEBUG: Column content fired for order ' . $order_id);
    }
}, 10, 2);
```

### Bước 5: Kiểm tra Error Log

1. Vào `/wp-content/debug.log`
2. Tìm dòng có "AOI DEBUG"
3. Xem hooks có được call không

### Bước 6: Thử Deactivate/Activate Plugin

1. Vão **Plugins**
2. **Deactivate** "Affiliate Order Integration"
3. **Activate** lại plugin
4. Kiểm tra **WooCommerce > Orders** có cột mới không

### Bước 7: Kiểm tra Database

Chạy query trong phpMyAdmin:

```sql
SELECT * FROM wp_options WHERE option_name LIKE '%aoi%';
SHOW TABLES LIKE '%aoi%';
```

### Bước 8: Manual Test Order

1. Vào trang shop với `?ctv=test123` trong URL
2. Thêm sản phẩm vào cart
3. Checkout để tạo order
4. Vào **WooCommerce > Orders** xem order mới

### Bước 9: Kiểm tra Theme/Plugin Conflicts

1. Tạm thời switch sang theme mặc định (Twenty Twenty-Four)
2. Deactive tất cả plugins khác trừ WooCommerce và AOI
3. Test lại xem có hiển thị không

### Bước 10: Check File Permissions

Đảm bảo thư mục plugin có quyền đọc:

```bash
chmod -R 755 /wp-content/plugins/affiliate-order-integration/
```

## Expected Results:

Sau khi debug, bạn sẽ thấy:

- ✅ Cột **"Affiliate Status"** trong danh sách orders
- ✅ Trạng thái hiển thị: ✓ Sent, ✗ Failed, ⏳ Pending, — No CTV
- ✅ Nút **"Resend"/"Send Now"** trong cột
- ✅ Meta box **"Affiliate Information"** khi edit order
- ✅ Menu **Settings > Affiliate Orders** và **WooCommerce > Affiliate Logs**

## Nếu vẫn không work:

1. Kiểm tra PHP version >= 7.4
2. Kiểm tra WordPress version >= 5.0  
3. Kiểm tra WooCommerce version >= 5.0
4. Contact để có direct support

## Common Issues:

- **Caching**: Clear all cache (plugin cache, server cache, CDN)
- **HPOS**: Nếu WooCommerce dùng High-Performance Order Storage, thử disable trong **WooCommerce > Settings > Advanced > Features**
- **Theme conflicts**: Một số theme custom có thể override WooCommerce hooks
