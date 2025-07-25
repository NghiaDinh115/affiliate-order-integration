# Affiliate Order Integration

Plugin WordPress tích hợp gửi đơn hàng đến website affiliate. Hỗ trợ đồng bộ đơn hàng WooCommerce với Sellmate affiliate network.

## Mô tả

Affiliate Order Integration là plugin WordPress chuyên dụng để tự động gửi đơn hàng từ WooCommerce đến hệ thống affiliate Sellmate. Plugin này cung cấp:

- Tích hợp API Sellmate (https://aff-api.sellmate.vn)
- Xử lý CTV token và tracking
- Tự động gửi đơn hàng khi thanh toán thành công
- Quản lý logs và resend đơn hàng
- Giao diện admin để cấu hình và monitor
- Hỗ trợ cài đặt qua Composer

## Yêu cầu hệ thống

- PHP 7.4 hoặc cao hơn
- WordPress 5.0 hoặc cao hơn
- WooCommerce 5.0 hoặc cao hơn
- Composer (cho việc cài đặt qua Composer)

## Cài đặt

### Phương pháp 1: Cài đặt thông qua Composer (Khuyến nghị)

#### Điều kiện tiên quyết:
- Website WordPress đã có WooCommerce
- Website đã có Composer setup
- Plugin đã được push lên GitHub repository

#### Các bước chi tiết:

**Bước 1: Backup composer.json**
```bash
cd /path/to/wordpress-site
cp composer.json composer.json.backup
```

**Bước 2: Thêm repository plugin**

*Từ GitHub (Production):*
```bash
composer config repositories.affiliate-order-integration vcs https://github.com/NghiaDinh115/affiliate-order-integration.git
```

*Từ Local (Development):*
```bash
composer config repositories.affiliate-order-integration path /path/to/local/plugin
```

**Bước 3: Cài đặt plugin**

*Từ GitHub:*
```bash
composer require sointech/affiliate-order-integration:dev-main
```

*Từ Local:*
```bash
composer require sointech/affiliate-order-integration:@dev
```

**Bước 4: Kiểm tra cài đặt**
```bash
ls -la wordpress/wp-content/plugins/affiliate-order-integration
```

**Bước 5: Kích hoạt plugin**
- Vào WordPress Admin → Plugins
- Tìm "Affiliate Order Integration"
- Click "Activate"

### Phương pháp 2: Cài đặt thủ công

1. Tải xuống plugin từ GitHub
2. Giải nén vào thư mục `wp-content/plugins/`
3. Kích hoạt plugin trong WordPress Admin

### Phương pháp 3: Template composer.json cho WordPress site mới

Nếu website chưa có Composer, tạo file `composer.json`:

```json
{
    "name": "your-company/wordpress-site",
    "type": "project",
    "repositories": [
        {
            "type": "composer",
            "url": "https://wpackagist.org"
        },
        {
            "type": "vcs",
            "url": "https://github.com/NghiaDinh115/affiliate-order-integration.git"
        }
    ],
    "require": {
        "composer/installers": "^1.12",
        "johnpbloch/wordpress": "^6.8",
        "wpackagist-plugin/woocommerce": "^8.0",
        "sointech/affiliate-order-integration": "dev-main"
    },
    "extra": {
        "wordpress-install-dir": "wordpress",
        "installer-paths": {
            "wordpress/wp-content/plugins/{$name}/": [
                "type:wordpress-plugin"
            ],
            "wordpress/wp-content/themes/{$name}/": [
                "type:wordpress-theme"
            ]
        }
    },
    "config": {
        "allow-plugins": {
            "johnpbloch/wordpress-core-installer": true,
            "composer/installers": true
        }
    }
}
```

### Quản lý Plugin

**Cập nhật Plugin:**
```bash
# Cập nhật lên version mới
composer update sointech/affiliate-order-integration

# Hoặc cập nhật tất cả packages
composer update
```

**Gỡ bỏ Plugin:**
```bash
# Deactivate trong WordPress Admin trước
composer remove sointech/affiliate-order-integration
```

## ⚠️ Lưu ý quan trọng

1. **WooCommerce Required**: Plugin cần WooCommerce đã được cài đặt và kích hoạt
2. **Sellmate API**: Cần có Partner ID và cấu hình API endpoint
3. **Backup**: Luôn backup website trước khi cài đặt plugin mới
4. **Test**: Test trên staging environment trước khi deploy production
5. **Version**: Hiện tại sử dụng dev-main, sẽ có version stable sau
6. **Permissions**: Đảm bảo server có quyền ghi file và tạo database table
7. **SSL**: Khuyến nghị sử dụng HTTPS cho API calls bảo mật

## 🎯 Ví dụ hoàn chỉnh

### Cài đặt trên website có sẵn WooCommerce:

```bash
# 1. Di chuyển đến thư mục WordPress
cd /Applications/XAMPP/xamppfiles/htdocs/your-wordpress-site

# 2. Backup (nếu đã có composer.json)
cp composer.json composer.json.backup

# 3. Thêm repository GitHub
composer config repositories.affiliate-order-integration vcs https://github.com/NghiaDinh115/affiliate-order-integration.git

# 4. Cài đặt plugin (hiện tại dùng dev-main)
composer require sointech/affiliate-order-integration:dev-main

# 5. Kiểm tra plugin đã được cài
ls -la wordpress/wp-content/plugins/affiliate-order-integration

# 6. Vào WordPress Admin để kích hoạt
# Admin → Plugins → Affiliate Order Integration → Activate
```

### Cài đặt để development:

```bash
# 1. Clone plugin về local
git clone https://github.com/NghiaDinh115/affiliate-order-integration.git /path/to/local/plugin

# 2. Trong WordPress site
cd /path/to/wordpress-site

# 3. Link local plugin
composer config repositories.affiliate-order-integration path /path/to/local/plugin

# 4. Cài đặt development version
composer require sointech/affiliate-order-integration:@dev

# 5. Plugin sẽ được symlink, thay đổi ở local sẽ reflect ngay
```

## Cấu hình

Sau khi cài đặt và kích hoạt plugin:

1. Vào **WordPress Admin > Affiliate Integration**
2. Cấu hình thông tin API:
   - **Partner ID**: ID đối tác từ Sellmate
   - **API Endpoint**: `https://aff-api.sellmate.vn/api/v1/partnerSystem/orderCreate`
   - **Enable Debug**: Bật để ghi chi tiết logs
3. **Test Connection** để kiểm tra kết nối API
4. Lưu thay đổi

### Cấu hình CTV Token

Plugin tự động xử lý CTV token thông qua:
- URL parameter: `?ctv=TOKEN`
- Cookie tracking trong 30 ngày
- Gửi kèm trong mỗi đơn hàng

## Tính năng

- ✅ **Tự động gửi đơn hàng**: Khi WooCommerce order hoàn thành
- ✅ **CTV Token Management**: Tracking và cookie management
- ✅ **Logs Management**: Xem và quản lý logs gửi đơn
- ✅ **Resend Orders**: Gửi lại đơn hàng thất bại
- ✅ **Test Connection**: Kiểm tra kết nối API
- ✅ **Debug Mode**: Ghi chi tiết logs để debug
- ✅ **Database Integration**: Lưu trữ logs trong database
- ✅ **Admin Interface**: Giao diện quản lý thân thiện

## Phát triển

### Thiết lập môi trường phát triển

```bash
# Clone repository
git clone https://github.com/NghiaDinh115/affiliate-order-integration.git

# Cài đặt dependencies
composer install

# Chạy tests
composer run test

# Kiểm tra coding standards
composer run cs

# Fix coding standards
composer run cbf

# Build production version
composer run build
```

### Cấu trúc thư mục

```
affiliate-order-integration/
├── admin/                    # Admin-specific files
│   ├── css/
│   ├── js/
│   └── views/
├── includes/                 # Core plugin classes
│   ├── class-admin.php      # Admin interface
│   ├── class-affiliate-api.php  # Sellmate API integration
│   └── class-order-handler.php # WooCommerce hooks
├── languages/               # Translation files
├── public/                  # Frontend assets
│   └── css/
├── tests/                   # Unit tests
├── composer.json           # Composer configuration
├── affiliate-order-integration.php # Main plugin file
└── README.md
```

## API Documentation

### Database Schema

Plugin tạo table `wp_aoi_order_logs` với cấu trúc:

```sql
CREATE TABLE wp_aoi_order_logs (
    id int(11) NOT NULL AUTO_INCREMENT,
    order_id bigint(20) NOT NULL,
    ctv_token varchar(255) DEFAULT NULL,
    api_response longtext,
    status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY order_id (order_id),
    KEY status (status)
);
```

### WooCommerce Hooks

Plugin hook vào các events sau:

- `woocommerce_thankyou` - Gửi đơn hàng sau khi thanh toán
- `woocommerce_order_status_completed` - Đơn hàng hoàn thành
- `woocommerce_order_status_processing` - Đơn hàng đang xử lý

### Sellmate API Integration

**Endpoint:** `https://aff-api.sellmate.vn/api/v1/partnerSystem/orderCreate`

**Request Format:**
```json
{
    "partnerId": 123,
    "orderCode": "WOO-12345",
    "orderValue": 1000000,
    "ctvToken": "ctv_token_here",
    "customerInfo": {
        "name": "Nguyen Van A",
        "phone": "0987654321",
        "email": "customer@example.com"
    },
    "products": [
        {
            "name": "Product Name",
            "price": 500000,
            "quantity": 2
        }
    ]
}
```

## Troubleshooting

### Plugin không gửi đơn hàng

1. Kiểm tra WooCommerce đã được kích hoạt
2. Xem logs trong **Affiliate Integration > Logs**
3. Bật Debug Mode để xem chi tiết
4. Test Connection với API

### CTV Token không hoạt động

1. Kiểm tra URL có parameter `?ctv=TOKEN`
2. Xem cookie `aoi_ctv_token` trong browser
3. Kiểm tra logs có ghi nhận token

### API Connection Error

1. Kiểm tra Partner ID đúng định dạng
2. Xác minh API endpoint URL
3. Kiểm tra server có thể kết nối internet
4. Xem response trong logs để debug

## Đóng góp

1. Fork repository trên GitHub
2. Tạo feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add some amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Tạo Pull Request

## Changelog

### 1.0.0
- Phiên bản đầu tiên
- Tích hợp Sellmate API
- CTV token management
- WooCommerce integration
- Admin interface
- Database logging
- Composer support

## License

GPL v2 or later. Xem [LICENSE](LICENSE) để biết thêm chi tiết.

## Support

- [GitHub Issues](https://github.com/NghiaDinh115/affiliate-order-integration/issues)
- [Documentation](https://github.com/NghiaDinh115/affiliate-order-integration/wiki)
- Email: contact@sointech.com
- Website: https://sointech.sointech.dev

## Roadmap

- [ ] Hỗ trợ multiple affiliate networks
- [ ] Dashboard analytics và báo cáo
- [ ] Bulk resend orders
- [ ] Webhook support từ Sellmate
- [ ] Export/Import cấu hình
- [ ] Multi-site support
