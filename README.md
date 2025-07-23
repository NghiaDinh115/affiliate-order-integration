# My Sample Plugin

Plugin WordPress mẫu có thể tái sử dụng với các tính năng cơ bản và hỗ trợ cài đặt qua Composer.

## Mô tả

My Sample Plugin là một plugin WordPress mẫu được thiết kế để có thể tái sử dụng và mở rộng dễ dàng. Plugin này cung cấp:

- Cấu trúc plugin chuẩn WordPress
- Hỗ trợ đa ngôn ngữ (i18n)
- Quản lý database tùy chỉnh
- Tách biệt logic admin và frontend
- PSR-4 autoloading
- Hỗ trợ cài đặt qua Composer

## Yêu cầu hệ thống

- PHP 7.4 hoặc cao hơn
- WordPress 5.0 hoặc cao hơn
- Composer (cho việc cài đặt qua Composer)

## Cài đặt

### Phương pháp 1: Cài đặt thông qua Composer (Khuyến nghị)

#### Điều kiện tiên quyết:
- Website WordPress đã có Composer setup
- Plugin đã được push lên GitLab/GitHub repository

#### Các bước chi tiết:

**Bước 1: Backup composer.json**
```bash
cd /path/to/wordpress-site
cp composer.json composer.json.backup
```

**Bước 2: Thêm repository plugin**

*Từ GitLab/GitHub (Production):*
```bash
composer config repositories.my-sample-plugin vcs https://gitlab.com/sointech/my-sample-plugin.git
```

*Từ Local (Development):*
```bash
composer config repositories.my-sample-plugin path /path/to/local/plugin
```

**Bước 3: Cài đặt plugin**

*Từ GitLab/GitHub:*
```bash
composer require sointech/my-sample-plugin:^1.0
```

*Từ Local:*
```bash
composer require sointech/my-sample-plugin:@dev
```

**Bước 4: Kiểm tra cài đặt**
```bash
ls -la wordpress/wp-content/plugins/my-sample-plugin
```

**Bước 5: Kích hoạt plugin**
- Vào WordPress Admin → Plugins
- Tìm "My Sample Plugin"
- Click "Activate"

### Phương pháp 2: Cài đặt thủ công

1. Tải xuống plugin từ GitLab/GitHub
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
        }
    ],
    "require": {
        "composer/installers": "^1.12",
        "johnpbloch/wordpress": "^6.8"
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
composer update sointech/my-sample-plugin

# Hoặc cập nhật tất cả packages
composer update
```

**Gỡ bỏ Plugin:**
```bash
# Deactivate trong WordPress Admin trước
composer remove sointech/my-sample-plugin
```

## ⚠️ Lưu ý quan trọng

1. **Backup**: Luôn backup website trước khi cài đặt plugin mới
2. **Test**: Test trên staging environment trước khi deploy production
3. **Version**: Sử dụng version cụ thể cho production (^1.0 thay vì @dev)
4. **Permissions**: Đảm bảo server có quyền ghi file
5. **Dependencies**: Kiểm tra PHP version và WordPress compatibility
6. **Security**: Plugin chỉ thêm vào wp-content/plugins/, không ảnh hưởng core WordPress

## 🎯 Ví dụ hoàn chỉnh

### Cài đặt trên website mới:

```bash
# 1. Di chuyển đến thư mục WordPress
cd /Applications/XAMPP/xamppfiles/htdocs/your-wordpress-site

# 2. Backup (nếu đã có composer.json)
cp composer.json composer.json.backup

# 3. Thêm repository GitLab
composer config repositories.my-sample-plugin vcs https://gitlab.com/sointech/my-sample-plugin.git

# 4. Cài đặt plugin (production)
composer require sointech/my-sample-plugin:^1.0

# 5. Kiểm tra plugin đã được cài
ls -la wordpress/wp-content/plugins/my-sample-plugin

# 6. Vào WordPress Admin để kích hoạt
# Admin → Plugins → My Sample Plugin → Activate
```

### Cài đặt để development:

```bash
# 1. Clone plugin về local
git clone https://gitlab.com/sointech/my-sample-plugin.git /path/to/local/plugin

# 2. Trong WordPress site
cd /path/to/wordpress-site

# 3. Link local plugin
composer config repositories.my-sample-plugin path /path/to/local/plugin

# 4. Cài đặt development version
composer require sointech/my-sample-plugin:@dev

# 5. Plugin sẽ được symlink, thay đổi ở local sẽ reflect ngay
```

## Cấu hình

Sau khi cài đặt và kích hoạt plugin:

1. Vào **WordPress Admin > Settings > My Sample Plugin**
2. Cấu hình các tùy chọn theo nhu cầu
3. Lưu thay đổi

## Tính năng

- ✅ Quản lý dữ liệu tùy chỉnh
- ✅ Giao diện admin thân thiện
- ✅ Hỗ trợ đa ngôn ngữ
- ✅ API endpoints tùy chỉnh
- ✅ Shortcodes hỗ trợ
- ✅ Widget tùy chỉnh

## Phát triển

### Thiết lập môi trường phát triển

```bash
# Clone repository
git clone https://gitlab.com/sointech/my-sample-plugin.git

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
my-sample-plugin/
├── admin/              # Admin-specific files
│   ├── css/
│   ├── js/
│   └── views/
├── assets/             # Public assets
├── includes/           # Core plugin classes
│   ├── class-admin.php
│   ├── class-frontend.php
│   └── class-plugin-core.php
├── languages/          # Translation files
├── public/             # Frontend assets
│   ├── css/
│   └── js/
├── tests/              # Unit tests
├── composer.json       # Composer configuration
├── my-sample-plugin.php # Main plugin file
└── README.md
```

## API Documentation

### Hooks

#### Actions

- `msp_plugin_loaded` - Được kích hoạt sau khi plugin load xong
- `msp_before_save_data` - Trước khi lưu dữ liệu
- `msp_after_save_data` - Sau khi lưu dữ liệu

#### Filters

- `msp_default_options` - Lọc các tùy chọn mặc định
- `msp_admin_menu_capability` - Lọc quyền truy cập menu admin

### Shortcodes

```php
// Hiển thị dữ liệu plugin
[msp_display id="1"]

// Hiển thị form
[msp_form type="contact"]
```

## Đóng góp

1. Fork repository trên GitLab
2. Tạo feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add some amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Tạo Merge Request

## Changelog

### 1.0.0
- Phiên bản đầu tiên
- Cấu trúc plugin cơ bản
- Hỗ trợ Composer
- PSR-4 autoloading
- WordPress Coding Standards
- PHPUnit testing
- CI/CD pipeline

## License

GPL v2 or later. Xem [LICENSE](LICENSE) để biết thêm chi tiết.

## Support

- [GitLab Issues](https://gitlab.com/sointech/my-sample-plugin/-/issues)
- [Documentation](https://gitlab.com/sointech/my-sample-plugin/-/wikis/home)
- Email: contact@sointech.com
- Website: https://sointech.sointech.dev

## Roadmap

- [ ] Thêm tính năng import/export
- [ ] Hỗ trợ REST API mở rộng
- [ ] Widget block cho Gutenberg
- [ ] Tích hợp với WooCommerce
- [ ] Dashboard analytics
