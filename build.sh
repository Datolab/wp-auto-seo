#!/bin/bash
set -e

# Define variables
PLUGIN_SLUG="datolab-auto-seo"
PLUGIN_FILE="$PLUGIN_SLUG.php"

# Get the current date in both formats
VERSION=$(date +"%Y%m%d")       # YYYYMMDD for plugin file and readme.txt
RELEASE_TAG=$(date +"%y.%m.%d") # YY.MM.DD for GitHub release tag

echo "Building $PLUGIN_SLUG version $VERSION..."

# Update the version in the main plugin file
sed -i '' "s/^\( \*\?Version:\s*\).*/\1$VERSION/" "$PLUGIN_FILE"

# Update the version in readme.txt
if [ -f "readme.txt" ]; then
    sed -i '' "s/^\(Stable tag:\s*\).*/\1$VERSION/" readme.txt
fi

# Define build directories
BUILD_DIR="./build"
RELEASE_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_FILE="$PLUGIN_SLUG-$RELEASE_TAG.zip"

# Clean up any previous build
rm -rf "$BUILD_DIR"
mkdir -p "$RELEASE_DIR"

# Copy plugin files to the release directory, excluding unnecessary files
rsync -av --exclude='build.sh' \
          --exclude='build' \
          --exclude='.git' \
          --exclude='.github' \
          --exclude='tests' \
          --exclude='README.md' \
          --exclude='readme.md' \
          --exclude='.gitignore' \
          --exclude='composer.json' \
          --exclude='composer.lock' \
          --exclude='package.json' \
          --exclude='package-lock.json' \
          --exclude='node_modules' \
          --exclude='*.zip' \
          --exclude='*.log' \
          --exclude='*.csv' \
          ./ "$RELEASE_DIR/"

# Navigate to the build directory
cd "$BUILD_DIR"

# Create the zip file with the release tag
zip -r "../$ZIP_FILE" "$PLUGIN_SLUG"

# Navigate back to the root directory
cd ..

# Remove the build directory
rm -rf "$BUILD_DIR"

echo "Build complete: $ZIP_FILE"
echo "Release tag version: $RELEASE_TAG"