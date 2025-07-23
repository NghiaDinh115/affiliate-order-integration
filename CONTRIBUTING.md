# Contributing to My Sample Plugin

Cảm ơn bạn đã quan tâm đến việc đóng góp cho My Sample Plugin! Chúng tôi hoan nghênh mọi đóng góp từ cộng đồng.

## Cách đóng góp

### Báo cáo lỗi (Bug Reports)

Trước khi báo cáo lỗi, vui lòng:

1. Kiểm tra [Issues](https://github.com/sointech/my-sample-plugin/issues) đã tồn tại
2. Đảm bảo bạn đang sử dụng phiên bản mới nhất
3. Kiểm tra [FAQ](https://github.com/sointech/my-sample-plugin/wiki/FAQ)

Khi báo cáo lỗi, vui lòng bao gồm:

- Mô tả chi tiết về lỗi
- Các bước để tái tạo lỗi
- Kết quả mong đợi vs kết quả thực tế
- Môi trường (PHP version, WordPress version, etc.)
- Screenshots nếu có thể

### Đề xuất tính năng (Feature Requests)

Chúng tôi hoan nghênh các đề xuất tính năng mới! Vui lòng:

1. Mở một [Issue](https://github.com/sointech/my-sample-plugin/issues) với label "enhancement"
2. Mô tả rõ tính năng bạn muốn
3. Giải thích tại sao tính năng này hữu ích
4. Đưa ra ví dụ sử dụng nếu có thể

### Pull Requests

1. **Fork** repository
2. **Clone** fork của bạn:
   ```bash
   git clone https://github.com/your-username/my-sample-plugin.git
   ```

3. **Tạo branch** cho feature/fix:
   ```bash
   git checkout -b feature/amazing-feature
   # hoặc
   git checkout -b fix/bug-description
   ```

4. **Cài đặt dependencies**:
   ```bash
   composer install
   ```

5. **Thực hiện thay đổi** và commit:
   ```bash
   git add .
   git commit -m "Add amazing feature"
   ```

6. **Chạy tests** và coding standards:
   ```bash
   composer run cs
   composer run test
   ```

7. **Push** lên branch:
   ```bash
   git push origin feature/amazing-feature
   ```

8. **Tạo Pull Request** trên GitHub

## Coding Standards

### PHP

Chúng tôi tuân thủ [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

```bash
# Kiểm tra coding standards
composer run cs

# Tự động fix một số vấn đề
composer run cbf
```

### Git Commit Messages

Sử dụng format sau cho commit messages:

```
type(scope): description

[optional body]

[optional footer]
```

**Types:**
- `feat`: Tính năng mới
- `fix`: Sửa lỗi
- `docs`: Cập nhật documentation
- `style`: Thay đổi formatting, không ảnh hưởng code
- `refactor`: Refactor code
- `test`: Thêm hoặc sửa tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(admin): add new dashboard widget
fix(frontend): resolve display issue on mobile
docs(readme): update installation instructions
```

## Development Setup

### Yêu cầu

- PHP 7.4+
- Composer
- WordPress 5.0+
- Git

### Thiết lập

1. **Clone repository**:
   ```bash
   git clone https://github.com/sointech/my-sample-plugin.git
   cd my-sample-plugin
   ```

2. **Cài đặt dependencies**:
   ```bash
   composer install
   ```

3. **Cài đặt coding standards**:
   ```bash
   composer run install-codestandards
   ```

4. **Chạy tests**:
   ```bash
   composer run test
   ```

### Cấu trúc Project

```
my-sample-plugin/
├── admin/              # Admin-specific files
├── assets/             # Public assets
├── includes/           # Core plugin classes (PSR-4)
├── languages/          # Translation files
├── public/             # Frontend assets
├── tests/              # Unit tests
├── .github/            # GitHub workflows
├── composer.json       # Composer configuration
├── phpcs.xml          # PHP CodeSniffer config
├── phpunit.xml        # PHPUnit configuration
└── my-sample-plugin.php # Main plugin file
```

## Testing

### Unit Tests

```bash
# Chạy tất cả tests
composer run test

# Chạy với coverage
vendor/bin/phpunit --coverage-html coverage/

# Chạy test cụ thể
vendor/bin/phpunit tests/TestClassName.php
```

### Integration Tests

Chúng tôi sử dụng WordPress testing framework:

```bash
# Thiết lập test database
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Chạy integration tests
composer run test:integration
```

## Release Process

1. Cập nhật version trong:
   - `composer.json`
   - `my-sample-plugin.php`
   - `CHANGELOG.md`

2. Commit và tag:
   ```bash
   git add .
   git commit -m "chore: bump version to 1.1.0"
   git tag -a v1.1.0 -m "Version 1.1.0"
   git push origin main --tags
   ```

3. Tạo release trên GitHub
4. CI/CD sẽ tự động build và deploy

## Code Review Process

1. Tất cả pull requests cần ít nhất 1 review
2. Maintainers sẽ review trong vòng 48 giờ
3. CI/CD phải pass trước khi merge
4. Squash commits khi merge

## Community Guidelines

- Tôn trọng mọi người trong cộng đồng
- Sử dụng ngôn ngữ chuyên nghiệp và lịch sự
- Tập trung vào vấn đề, không cá nhân hóa
- Giúp đỡ người mới bắt đầu
- Chia sẻ kiến thức và kinh nghiệm

## Liên hệ

- Issues: [GitHub Issues](https://github.com/sointech/my-sample-plugin/issues)
- Email: contact@sointech.com
- Discord: [Server link]

## License

Bằng việc đóng góp, bạn đồng ý rằng contributions của bạn sẽ được license dưới GPL v2 or later license.
