#!/bin/bash

# Setup development environment for Affiliate Order Integration Plugin

echo "🚀 Setting up development environment..."

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --dev

# Create logs directory
echo "📁 Creating logs directory..."
mkdir -p wp-content/logs

# Set permissions
echo "🔐 Setting permissions..."
chmod 755 wp-content/logs

# Run initial code standards check
echo "🔍 Running code standards check..."
composer run cs || echo "⚠️  Code standards issues found - run 'composer run cbf' to fix"

# Run tests
echo "🧪 Running tests..."
composer test || echo "⚠️  Some tests failed"

# Generate autoloader
echo "🔄 Generating optimized autoloader..."
composer dump-autoload -o

echo "✅ Development environment setup complete!"
echo ""
echo "Available commands:"
echo "  composer test          - Run all tests"
echo "  composer run cs        - Check coding standards"
echo "  composer run cbf       - Fix coding standards"
echo "  composer run lint      - Lint PHP files"
echo "  composer run build     - Build production version"
echo ""
