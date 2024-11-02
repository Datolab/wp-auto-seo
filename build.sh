#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Define variables
PLUGIN_SLUG="datolab-auto-seo"
BUILD_DIR="./build"
RELEASE_DIR="$BUILD_DIR/$PLUGIN_SLUG"
VERSION=$(grep "Version:" "$PLUGIN_SLUG.php" | awk '{print $2}')
ZIP_FILE="$PLUGIN_SLUG-v$VERSION.zip"

echo "Building $PLUGIN_SLUG version $VERSION..."

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

# Create the zip file
zip -r "../$ZIP_FILE" "$PLUGIN_SLUG"

# Navigate back to the root directory
cd ..

# Remove the build directory
rm -rf "$BUILD_DIR"

echo "Build complete: $ZIP_FILE"