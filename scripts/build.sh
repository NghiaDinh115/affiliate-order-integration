#!/bin/bash

# Build production version of Affiliate Order Integration Plugin

echo "🏗️  Building production version..."

# Create build directory
BUILD_DIR="build"
PLUGIN_DIR="affiliate-order-integration"

rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR/$PLUGIN_DIR

echo "📁 Copying plugin files..."

# Copy main plugin files
cp affiliate-order-integration.php $BUILD_DIR/$PLUGIN_DIR/
cp README.md $BUILD_DIR/$PLUGIN_DIR/
cp composer.json $BUILD_DIR/$PLUGIN_DIR/

# Copy directories
cp -r includes/ $BUILD_DIR/$PLUGIN_DIR/
cp -r admin/ $BUILD_DIR/$PLUGIN_DIR/
cp -r languages/ $BUILD_DIR/$PLUGIN_DIR/

# Install production dependencies only
echo "📦 Installing production dependencies..."
cd $BUILD_DIR/$PLUGIN_DIR
composer install --no-dev --optimize-autoloader --no-interaction

# Remove development files
echo "🧹 Cleaning up development files..."
rm -rf tests/
rm -rf scripts/
rm -f phpunit.xml
rm -f phpcs.xml
rm -f .gitignore

cd ../../

# Create ZIP file
echo "📦 Creating ZIP package..."
cd $BUILD_DIR
zip -r affiliate-order-integration.zip $PLUGIN_DIR/
cd ..

echo "✅ Build complete!"
echo "📦 Package created: $BUILD_DIR/affiliate-order-integration.zip"
echo "📁 Build directory: $BUILD_DIR/$PLUGIN_DIR/"
