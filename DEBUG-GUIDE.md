# Debug Hướng Dẫn: Kiểm tra Plugin Affiliate Order Integration

## 1. Kiểm tra Plugin có Active không

1. Vào **WordPress Admin > Plugins**
2. Tìm plugin "Affiliate Order Integration" 
3. Đảm bảo nó được **Activate**

## 2. Kiểm tra WooCommerce có active không

1. Vào **WordPress Admin > Plugins**
2. Đảm bảo **WooCommerce** được activate
3. Thử vào **WooCommerce > Orders** xem có hiển thị đơn hàng không

## 3. Kiểm tra Database Table

Chạy query này trong **phpMyAdmin** hoặc database tool:

```sql
SHOW TABLES LIKE 'wp_aoi_affiliate_orders';
```

Nếu table không tồn tại, deactivate và activate lại plugin.

## 4. Kiểm tra Plugin Settings

1. Vào **Settings > Affiliate Orders**
2. Đảm bảo có **Partner ID** (default: 1)
3. **Auto Send Orders** được check
4. Test connection để đảm bảo API hoạt động

## 5. Kiểm tra Logs

1. Vào **WooCommerce > Affiliate Logs**
2. Xem có logs nào không
3. Nếu không có logs, tạo test order với CTV token

## 6. Test tạo Order với CTV Token

1. Thêm `?ctv=test123` vào URL trang sản phẩm
2. Mua sản phẩm để tạo order
3. Kiểm tra order trong **WooCommerce > Orders**
4. Xem có cột **Affiliate Status** không

## 7. Kiểm tra Developer Console

1. Mở **WooCommerce > Orders**
2. Nhấn **F12** để mở Developer Tools
3. Vào tab **Console**
4. Refresh trang và xem có lỗi JavaScript không

## 8. Kiểm tra Admin Scripts được load

1. Vào **WooCommerce > Orders**
2. Nhấn **F12** > tab **Network**
3. Refresh trang
4. Tìm file **admin.css** và **admin.js**
5. Đảm bảo chúng được load thành công (status 200)

## 9. Debug PHP Errors

Thêm code này vào `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Rồi kiểm tra file `/wp-content/debug.log` để xem lỗi PHP.

## 10. Manual Check

Nếu vẫn không thấy cột, thử thêm code này vào `functions.php` để debug:

```php
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] == 'wc-orders') {
        echo '<div class="notice notice-info"><p>DEBUG: Đang ở trang WC Orders</p></div>';
    }
});

add_filter('manage_edit-shop_order_columns', function($columns) {
    error_log('DEBUG: Columns hook fired - ' . print_r(array_keys($columns), true));
    return $columns;
});
```

## Kết quả mong đợi:

- ✅ Cột **"Affiliate Status"** xuất hiện trong danh sách orders
- ✅ Hiển thị trạng thái: ✓ Sent, ✗ Failed, ⏳ Pending, — No CTV  
- ✅ Có nút **"Resend"** hoặc **"Send Now"** trong cột
- ✅ Meta box **"Affiliate Information"** trong order edit page
- ✅ Menu **"Settings > Affiliate Orders"** và **"WooCommerce > Affiliate Logs"**

## Nếu vẫn không hoạt động:

1. Deactivate plugin
2. Delete plugin folder
3. Re-upload từ GitHub
4. Activate lại plugin
5. Kiểm tra lại từ bước 1
