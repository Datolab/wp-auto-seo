#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Define variables
PLUGIN_SLUG="datolab-auto-seo"
PLUGIN_FILE="$PLUGIN_SLUG.php"

# Get the current date in YYYYMMDD format
CURRENT_DATE=$(date +"%Y%m%d")

# Get the number of commits made today
COUNTER=$(git log --since=midnight --oneline | wc -l)
COUNTER=$(printf "%02d" $((COUNTER - 1))) # Subtract 1 for the current commit

# Construct the new version string
NEW_VERSION="${CURRENT_DATE}${COUNTER}"

echo "Updating version to $NEW_VERSION"

# Update the version in the main plugin file
sed -i "s/^\( \*\?Version:\s*\).*/\1$NEW_VERSION/" "$PLUGIN_FILE"

# Update the version in readme.txt (if applicable)
if [ -f "readme.txt" ]; then
    sed -i "s/^\(Stable tag:\s*\).*/\1$NEW_VERSION/" readme.txt
fi

# Rest of your build script...

# Define build directories and variables as before
BUILD_DIR="./build"
RELEASE_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_FILE="$PLUGIN_SLUG-v$NEW_VERSION.zip"

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