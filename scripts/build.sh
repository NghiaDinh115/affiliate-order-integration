#!/bin/bash

# Build script for production release
# Usage: ./scripts/build.sh [version]

set -e

VERSION=${1:-"dev"}
BUILD_DIR="build"
PLUGIN_NAME="my-sample-plugin"
PLUGIN_FILE="my-sample-plugin.php"

echo "🏗️  Building My Sample Plugin v$VERSION..."

# Clean previous build
echo "🧹 Cleaning previous build..."
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR

# Create build directory
BUILD_PATH="$BUILD_DIR/$PLUGIN_NAME"
mkdir -p $BUILD_PATH

echo "📦 Copying files..."

# Copy plugin files (exclude development files)
rsync -av \
    --exclude=".git*" \
    --exclude="node_modules/" \
    --exclude="tests/" \
    --exclude="scripts/" \
    --exclude="build/" \
    --exclude="coverage/" \
    --exclude="logs/" \
    --exclude=".DS_Store" \
    --exclude="phpcs.xml" \
    --exclude="phpunit.xml" \
    --exclude="COMPOSER_INSTALL.md" \
    --exclude="CONTRIBUTING.md" \
    --exclude=".github/" \
    --exclude="composer.lock" \
    . $BUILD_PATH/

# Install production dependencies only
if [ -f "$BUILD_PATH/composer.json" ]; then
    echo "📚 Installing production dependencies..."
    cd $BUILD_PATH
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Remove composer files from final build
    rm -f composer.json composer.lock
    cd - > /dev/null
fi

# Update version in plugin file if specified
if [ "$VERSION" != "dev" ]; then
    echo "🔢 Updating version to $VERSION..."
    sed -i.bak "s/Version: [0-9.]*/Version: $VERSION/" "$BUILD_PATH/$PLUGIN_FILE"
    sed -i.bak "s/MSP_PLUGIN_VERSION', '[0-9.]*'/MSP_PLUGIN_VERSION', '$VERSION'/" "$BUILD_PATH/$PLUGIN_FILE"
    rm -f "$BUILD_PATH/$PLUGIN_FILE.bak"
fi

# Create ZIP file
echo "📦 Creating ZIP archive..."
cd $BUILD_DIR
zip -r "$PLUGIN_NAME-$VERSION.zip" $PLUGIN_NAME/ -x "*.DS_Store*" "*/.DS_Store*"
cd - > /dev/null

# Calculate file size
SIZE=$(du -h "$BUILD_DIR/$PLUGIN_NAME-$VERSION.zip" | cut -f1)

echo "✅ Build completed!"
echo ""
echo "📄 Build info:"
echo "  Version: $VERSION"
echo "  File: $BUILD_DIR/$PLUGIN_NAME-$VERSION.zip"
echo "  Size: $SIZE"
echo ""
echo "🚀 Ready for deployment!"
