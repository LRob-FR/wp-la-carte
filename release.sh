#!/bin/bash

# LRob La Carte - Release Builder
# This script generates translation files and creates a distributable zip archive

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_SLUG="lrob-la-carte"
PLUGIN_FILE="${PLUGIN_SLUG}.php"
LANGUAGES_DIR="languages"

# Print colored message
print_status() {
    echo -e "${BLUE}==>${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}!${NC} $1"
}

# Check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check dependencies
check_dependencies() {
    print_status "Checking dependencies..."

    local missing_deps=0

    # Check PHP
    if ! command_exists php; then
        print_error "PHP is not installed"
        echo "  Install with: sudo dnf install php-cli"
        missing_deps=1
    else
        print_success "PHP $(php -r 'echo PHP_VERSION;') found"
    fi

    # Check WP-CLI
    if ! command_exists wp; then
        print_error "WP-CLI is not installed"
        echo "  Install with: sudo dnf install wp-cli"
        echo "  Or manually: curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
        missing_deps=1
    else
        print_success "WP-CLI $(wp --version | grep -oP '\d+\.\d+\.\d+') found"
    fi

    # Check msgfmt (gettext)
    if ! command_exists msgfmt; then
        print_error "msgfmt (gettext) is not installed"
        echo "  Install with: sudo dnf install gettext"
        missing_deps=1
    else
        print_success "msgfmt $(msgfmt --version | head -1 | grep -oP '\d+\.\d+\.\d+') found"
    fi

    # Check zip
    if ! command_exists zip; then
        print_error "zip is not installed"
        echo "  Install with: sudo dnf install zip"
        missing_deps=1
    else
        print_success "zip found"
    fi

    if [ $missing_deps -eq 1 ]; then
        print_error "Missing dependencies. Please install them and try again."
        exit 1
    fi

    echo ""
}

# Extract version from plugin file
get_current_version() {
    grep -oP "Version:\s*\K[\d.]+" "$PLUGIN_FILE"
}

# Generate POT template
generate_pot() {
    print_status "Generating translation template (.pot)..."

    mkdir -p "$LANGUAGES_DIR"

    wp i18n make-pot . "$LANGUAGES_DIR/${PLUGIN_SLUG}.pot" \
        --domain="$PLUGIN_SLUG" \
        --package-name="LRob La Carte" \
        --skip-js

    if [ $? -eq 0 ]; then
        print_success "POT file generated: $LANGUAGES_DIR/${PLUGIN_SLUG}.pot"
    else
        print_error "Failed to generate POT file"
        exit 1
    fi
}

# Compile translations
compile_translations() {
    print_status "Compiling translations (.po → .mo)..."

    local compiled=0

    for po_file in "$LANGUAGES_DIR"/*.po; do
        if [ -f "$po_file" ]; then
            mo_file="${po_file%.po}.mo"

            msgfmt -o "$mo_file" "$po_file" 2>/dev/null

            if [ $? -eq 0 ]; then
                print_success "Compiled $(basename "$mo_file")"
                compiled=$((compiled + 1))
            else
                print_error "Failed to compile $(basename "$po_file")"
            fi
        fi
    done

    if [ $compiled -eq 0 ]; then
        print_warning "No .po files found to compile"
    fi
}

# Create release archive
create_archive() {
    local version=$1
    local archive_name="${PLUGIN_SLUG}-${version}.zip"

    print_status "Creating release archive..."

    mkdir -p ../releases

    # Remove old archive if exists
    [ -f "../releases/$archive_name" ] && rm "../releases/$archive_name"

    # Create zip excluding development files
    zip -r "../releases/$archive_name" \
        . \
        -x "*.git*" \
        -x "*node_modules*" \
        -x "*.sh" \
        -x "releases/*" \
        -x "*.po" \
        -x "*.pot" \
        -x "example-export.json" \
        >/dev/null

    if [ $? -eq 0 ]; then
        local size=$(du -h "../releases/$archive_name" | cut -f1)
        print_success "Archive created: ../releases/$archive_name ($size)"
    else
        print_error "Failed to create archive"
        exit 1
    fi
}

# Main script
main() {
    echo ""
    echo "╔════════════════════════════════════════╗"
    echo "║   LRob La Carte - Release Builder     ║"
    echo "╚════════════════════════════════════════╝"
    echo ""

    # Check dependencies first
    check_dependencies

    # Get current version
    VERSION=$(get_current_version)
    print_status "Current version: $VERSION"
    echo ""

    # Build process
    generate_pot
    compile_translations
    create_archive "$VERSION"

    echo ""
    print_success "Release $VERSION completed successfully! 🎉"
    echo ""
    echo "Next steps:"
    echo "  1. Test the plugin: unzip ../releases/${PLUGIN_SLUG}-${VERSION}.zip"
    echo "  2. Upload to WordPress"
    echo ""
}

# Run main function
main "$@"
