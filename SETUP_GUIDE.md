# 🎯 Hướng dẫn hoàn chỉnh: Plugin WordPress với Composer

## ✅ Những gì đã được thiết lập

### 📁 Cấu trúc File
```
my-sample-plugin/
├── 📄 my-sample-plugin.php      # Main plugin file với Composer support
├── 📄 composer.json             # Composer configuration
├── 📄 README.md                 # Documentation chính
├── 📄 COMPOSER_INSTALL.md       # Hướng dẫn cài đặt qua Composer
├── 📄 CONTRIBUTING.md           # Hướng dẫn đóng góp
├── 📄 CHANGELOG.md              # Lịch sử thay đổi
├── 📄 phpcs.xml                 # PHP CodeSniffer config
├── 📄 .gitignore                # Git ignore rules
├── 📁 .github/workflows/        # GitHub Actions CI/CD
├── 📁 scripts/                  # Build và setup scripts
├── 📁 admin/                    # Admin files
├── 📁 includes/                 # Core classes (PSR-4)
├── 📁 public/                   # Frontend assets
├── 📁 languages/                # Translation files
└── 📁 assets/                   # Public assets
```

### 🚀 Tính năng đã cài đặt

1. **Composer Support**
   - PSR-4 autoloading
   - Production & development dependencies
   - Scripts tự động (test, cs, cbf)

2. **Code Quality**
   - WordPress Coding Standards
   - PHPUnit testing framework
   - PHP CodeSniffer configuration

3. **CI/CD**
   - GitHub Actions workflow
   - Automated testing cho multiple PHP/WP versions
   - Security audit
   - Automatic release packaging

4. **Documentation**
   - Comprehensive README
   - Installation guides
   - Contributing guidelines
   - API documentation

## 📋 Các bước tiếp theo

### 1. Khởi tạo Development Environment

```bash
# Chạy setup script
./scripts/setup-dev.sh

# Hoặc manual setup
composer install --dev
composer run install-codestandards
```

### 2. Tạo GitHub Repository

```bash
# Khởi tạo git
git init
git add .
git commit -m "Initial commit: WordPress plugin with Composer support"

# Tạo repository trên GitHub, sau đó:
git remote add origin https://github.com/sointech/my-sample-plugin.git
git branch -M main
git push -u origin main
```

### 3. Thiết lập Packagist (cho public packages)

1. Đăng ký tại [Packagist.org](https://packagist.org)
2. Submit package với URL: `https://github.com/sointech/my-sample-plugin`
3. Setup GitHub webhook cho auto-update

### 4. Thiết lập Private Repository (cho private packages)

#### Option A: Private Packagist
```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://repo.packagist.com/your-organization/"
        }
    ]
}
```

#### Option B: Satis (Self-hosted)
```bash
# Cài đặt Satis
composer create-project composer/satis

# Cấu hình satis.json
{
    "name": "My Private Repo",
    "homepage": "https://packages.example.com",
    "repositories": [
        { "type": "vcs", "url": "https://github.com/sointech/my-sample-plugin" }
    ],
    "require-all": true
}
```

## 🛠️ Cách sử dụng cho developers

### Cài đặt plugin vào WordPress project

#### Method 1: Composer với Git
```bash
# Thêm vào composer.json
composer config repositories.my-sample-plugin vcs https://github.com/sointech/my-sample-plugin
composer require sointech/my-sample-plugin
```

#### Method 2: WPackagist (sau khi submit lên WordPress.org)
```bash
composer require wpackagist-plugin/my-sample-plugin
```

#### Method 3: Private Packagist
```bash
composer require sointech/my-sample-plugin
```

### Development Workflow

```bash
# 1. Clone và setup
git clone https://github.com/sointech/my-sample-plugin.git
cd my-sample-plugin
./scripts/setup-dev.sh

# 2. Development
# - Edit code
# - Add tests

# 3. Quality checks
composer run cs          # Check coding standards
composer run cbf         # Auto-fix coding standards
composer run test        # Run tests

# 4. Build for production
./scripts/build.sh 1.0.1

# 5. Release
git tag v1.0.1
git push origin v1.0.1   # Triggers GitHub Actions
```

## 📦 Cách deploy cho production

### Automatic Deployment (GitHub Actions)
1. Push code lên GitHub
2. Create release tag: `git tag v1.0.0 && git push origin v1.0.0`
3. GitHub Actions sẽ tự động:
   - Run tests
   - Build production package
   - Create release với ZIP file

### Manual Deployment
```bash
# Build production version
./scripts/build.sh 1.0.0

# Upload build/my-sample-plugin-1.0.0.zip lên server
# Hoặc submit lên WordPress.org
```

## 🎯 Best Practices đã được implement

1. **Semantic Versioning**: v1.0.0 format
2. **PSR-4 Autoloading**: Chuẩn PHP namespacing
3. **WordPress Coding Standards**: WPCS compliance
4. **Security**: Input validation, nonce verification
5. **Internationalization**: Text domain và translation ready
6. **Documentation**: Comprehensive docs
7. **Testing**: Unit tests setup
8. **CI/CD**: Automated workflows

## 🚀 Mở rộng plugin

### Thêm tính năng mới
1. Tạo class mới trong `includes/`
2. Follow PSR-4 namespace: `MySamplePlugin\\ClassName`
3. Autoload sẽ tự động hoạt động

### Thêm admin pages
1. Extend `MySamplePlugin\\Admin` class
2. Add menu items và handlers
3. Create view files trong `admin/views/`

### Thêm frontend features
1. Extend `MySamplePlugin\\Frontend` class
2. Add shortcodes, widgets, hooks
3. Enqueue CSS/JS trong `public/`

## 📞 Support

- **GitHub Issues**: Bug reports và feature requests
- **Documentation**: README.md và wiki
- **Email**: contact@sointech.com

---

🎉 **Plugin của bạn đã sẵn sàng cho production và có thể được cài đặt qua Composer!**
