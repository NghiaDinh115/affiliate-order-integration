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

```bash
# Thêm repository tùy chỉnh vào composer.json của dự án WordPress
composer config repositories.my-sample-plugin vcs https://github.com/sointech/my-sample-plugin

# Cài đặt plugin
composer require sointech/my-sample-plugin
```

### Phương pháp 2: Cài đặt thủ công

1. Tải xuống plugin từ GitHub
2. Giải nén vào thư mục `wp-content/plugins/`
3. Kích hoạt plugin trong WordPress Admin

### Phương pháp 3: Sử dụng Composer với Packagist tùy chỉnh

Nếu bạn có private Packagist:

```bash
composer require sointech/my-sample-plugin
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
git clone https://github.com/sointech/my-sample-plugin.git

# Cài đặt dependencies
composer install

# Chạy tests
composer run test

# Kiểm tra coding standards
composer run cs
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

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add some amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Tạo Pull Request

## Changelog

### 1.0.0
- Phiên bản đầu tiên
- Cấu trúc plugin cơ bản
- Hỗ trợ Composer

## License

GPL v2 or later. Xem [LICENSE](LICENSE) để biết thêm chi tiết.

## Support

- [GitHub Issues](https://github.com/sointech/my-sample-plugin/issues)
- [Documentation](https://github.com/sointech/my-sample-plugin/wiki)
- Email: contact@sointech.com
