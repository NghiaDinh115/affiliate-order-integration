#!/bin/bash

# Development setup script
# Usage: ./scripts/setup-dev.sh

set -e

echo "🚀 Setting up development environment for My Sample Plugin..."

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --dev

# Install WordPress Coding Standards (will be automatically installed by composer)
echo "📋 Setting up WordPress Coding Standards..."
if composer run-script --list | grep -q "install-codestandards"; then
    composer run install-codestandards
else
    echo "ℹ️  WordPress Coding Standards will be configured automatically by dealerdirect/phpcodesniffer-composer-installer"
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p tests/coverage
mkdir -p languages
mkdir -p build
mkdir -p logs

# Set up git hooks (optional)
if [ -d ".git" ]; then
    echo "🎣 Setting up git hooks..."
    cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
# Run coding standards check before commit
composer run cs
if [ $? -ne 0 ]; then
    echo "❌ Coding standards check failed. Please fix the issues."
    exit 1
fi
EOF
    chmod +x .git/hooks/pre-commit
fi

# Check PHP version
echo "🐘 Checking PHP version..."
php_version=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $php_version"

if php -r "exit(version_compare(PHP_VERSION, '7.4.0', '>=') ? 0 : 1);"; then
    echo "✅ PHP version requirement met!"
else
    echo "❌ PHP 7.4 or higher is required. Current version: $php_version"
    exit 1
fi

echo "✅ Development environment setup completed!"
echo ""
echo "Next steps:"
echo "  1. composer run cs     # Check coding standards"
echo "  2. composer run test   # Run tests"
echo "  3. composer run cbf    # Auto-fix coding standards"
echo ""
echo "Happy coding! 🎉"
