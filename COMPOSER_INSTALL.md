# Hướng dẫn cài đặt Plugin qua Composer

## Tổng quan

Plugin này hỗ trợ cài đặt qua Composer theo nhiều cách khác nhau, phù hợp với các dự án WordPress hiện đại.

## Phương pháp 1: Sử dụng Composer với WPackagist

Thêm vào `composer.json` của dự án WordPress:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://wpackagist.org"
        }
    ],
    "require": {
        "wpackagist-plugin/my-sample-plugin": "*"
    },
    "extra": {
        "installer-paths": {
            "wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
        }
    }
}
```

## Phương pháp 2: Cài đặt từ Git Repository

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/sointech/my-sample-plugin"
        }
    ],
    "require": {
        "sointech/my-sample-plugin": "dev-main"
    },
    "extra": {
        "installer-paths": {
            "wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
        }
    }
}
```

## Phương pháp 3: Private Packagist/Satis

Nếu bạn có private package repository:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://your-private-packagist.com"
        }
    ],
    "require": {
        "sointech/my-sample-plugin": "^1.0"
    }
}
```

## Cấu hình Bedrock/Roots

Cho dự án sử dụng Bedrock:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://wpackagist.org"
        },
        {
            "type": "vcs",
            "url": "https://github.com/sointech/my-sample-plugin"
        }
    ],
    "require": {
        "sointech/my-sample-plugin": "^1.0"
    },
    "extra": {
        "installer-paths": {
            "web/app/plugins/{$name}/": ["type:wordpress-plugin"],
            "web/app/mu-plugins/{$name}/": ["type:wordpress-muplugin"]
        }
    }
}
```

## Cài đặt cho Development

```bash
# Clone repository
git clone https://github.com/sointech/my-sample-plugin.git

# Di chuyển vào thư mục plugin
cd my-sample-plugin

# Cài đặt dependencies
composer install

# Cài đặt dev dependencies
composer install --dev
```

## Scripts hữu ích

Sau khi cài đặt, bạn có thể sử dụng các scripts sau:

```bash
# Chạy PHP CodeSniffer
composer run cs

# Tự động fix coding standards
composer run cbf

# Chạy unit tests
composer run test

# Cài đặt WordPress Coding Standards
composer run install-codestandards
```

## Tự động cập nhật

Để tự động cập nhật plugin:

```bash
# Cập nhật tất cả packages
composer update

# Cập nhật chỉ plugin này
composer update sointech/my-sample-plugin

# Cập nhật với constraint cụ thể
composer require sointech/my-sample-plugin:^1.1
```

## Troubleshooting

### Lỗi thường gặp

1. **Plugin không được cài vào đúng thư mục**
   ```bash
   composer config extra.installer-paths.wp-content/plugins/{\$name}/ "type:wordpress-plugin"
   ```

2. **Autoloader không hoạt động**
   - Đảm bảo file `vendor/autoload.php` tồn tại
   - Kiểm tra PSR-4 mapping trong `composer.json`

3. **Conflict với plugins khác**
   ```bash
   composer diagnose
   composer clear-cache
   ```

### Debug Composer

```bash
# Xem thông tin chi tiết về package
composer show sointech/my-sample-plugin

# Debug autoloader
composer dump-autoload -o

# Verify installation
composer validate
```

## Best Practices

1. **Version Constraints**: Sử dụng semantic versioning
   ```json
   "sointech/my-sample-plugin": "^1.0"  // >= 1.0.0, < 2.0.0
   ```

2. **Lock File**: Commit `composer.lock` cho production
   ```bash
   git add composer.lock
   ```

3. **Environment-specific configs**:
   ```json
   {
       "require": {
           "sointech/my-sample-plugin": "^1.0"
       },
       "require-dev": {
           "sointech/my-sample-plugin": "dev-main"
       }
   }
   ```

4. **Security**: Sử dụng `composer audit` thường xuyên
   ```bash
   composer audit
   ```
