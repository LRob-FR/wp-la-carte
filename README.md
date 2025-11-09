# LRob - La Carte

A professional menu management plugin for bars and restaurants on WordPress.

![Aperçu Front - La Carte](https://www.lrob.fr/wp-content/uploads/2025/11/La-Carte-Preview.png)

## Features

- **Product Management**: Organize your menu items by categories
- **Hierarchical Categories**: Support for nested categories
- **Multiple Prices**: Set different prices per product (size, format, etc.)
- **Happy Hour**: Mark special pricing with visual indicators
- **Allergen Information**: Display allergen warnings
- **Badges**: Highlight special items (new, recommended, spicy, etc.)
- **Availability Status**: Mark items as available, out of stock, or on request
- **Gutenberg Block**: Display your menu with customizable styling
- **Multilingual Ready**: Full i18n support (English + French included)
- **Import/Export**: Backup and restore your menu data

## Prerequisite
- **Gutenberg Page**: We only provide a Gutenberg block. No shortcode.

## Installation

1. Download the latest release ZIP
2. Upload to WordPress via Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Go to Menu → Categories to create your first categories

## Usage

### Creating Categories

![Categories - La Carte](https://www.lrob.fr/wp-content/uploads/2025/11/La-Carte-categories.png)

1. Navigate to **Menu → Categories**
2. Click **"Create Default Categories"** for a quick start, or
3. Click **"Add Category"** to create custom categories
4. Organize with drag & drop, add icons (emoji or image)

![Single Category Edit - La Carte](https://www.lrob.fr/wp-content/uploads/2025/11/La-Cart-category-edit.png)

### Adding Products

![Edit Product - La Carte](https://www.lrob.fr/wp-content/uploads/2025/11/La-Carte-product-edit.png)

1. Go to **Menu → Products**
2. Click **"Add Product"**
3. Fill in product details:
   - Name and description
   - Category
   - Image (optional)
   - Prices (add multiple if needed)
   - Allergens and badges
   - Availability status

### Display Your Menu

![Gutenberg Edit - La Carte](https://www.lrob.fr/wp-content/uploads/2025/11/La-Carte-Gutenberg-Options-showall.png)

1. Edit any page or post
2. Add the **"Menu Display"** block
3. Configure display options:
   - Show full menu or single category
   - Layout style (compact or classic)
   - Colors and typography
   - Show/hide images, descriptions, allergens

## Development

### Building a Release

Requirements:
- PHP CLI
- WP-CLI
- gettext (msgfmt)
- zip

```bash
# Install dependencies (Fedora/RHEL)
sudo dnf install php-cli php-mbstring wp-cli gettext zip

# Build release
cd lrob-la-carte/
./release.sh
```

The script will:
1. Generate translation template (.pot)
2. Compile translations (.po → .mo)
3. Create a clean ZIP in `../releases/`

### Translation

The plugin is translation-ready with English as the default language.

**Included translations:**
- French (fr_FR)

**Adding a new language:**

```bash
# Create translation file
cp languages/lrob-la-carte-fr_FR.po languages/lrob-la-carte-es_ES.po

# Edit the file and translate msgstr values
nano languages/lrob-la-carte-es_ES.po

# Build release (automatically compiles all .po files)
./release.sh
```

## Technical Details

- **WordPress Version**: 6.8+
- **PHP Version**: 8.4+
- **Text Domain**: `lrob-la-carte`
- **Database Tables**: 
  - `wp_lrob_categories`
  - `wp_lrob_products`
  - `wp_lrob_product_prices`

## Support

For support, please [open an issue](https://github.com/LRob-FR/wp-la-carte/issues) or [contact LRob directly](https://www.lrob.fr/contact/)

## Credits

**Developed by [LRob, Hébergeur web spécialiste WordPress](https://www.lrob.fr/)**  

## License

LRob - La Carte
Copyright (c) 2025 LRob

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <https://www.gnu.org/licenses/>.

For more details, see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

## Changelog

### 1.0.0
- Initial release
- Complete English translation with i18n support
- Fixed front sorting, products hirerarchy and rendering
- French translation included
- Smart category creation (user-controlled, language-aware)
- Professional release build system

### 1.1.0
- Clean unused FontAwesome code
- Fixed product image management (overhaul)
- Removed not working product search in product management panel
- Made product management panel navigation similar to frontend
- Adapted product image to be a miniature image
- Improve product cards display by avoiding blank spaces

### 1.1.1
- Fixed property is_happy_hour
- Fixed JSON Export/import

### 1.2.0
- **Security overhaul**
  - Hardened all PHP functions: added nonce validation, strict input sanitization & output escaping.  
  - Restricted SQL operations with `$wpdb->prepare()` and whitelisted table names.  
  - Improved AJAX handlers security and error handling.  
  - Prevented XSS, CSRF, and injection vectors across admin and frontend.

- **JavaScript & admin improvements**
  - Rewritten JS logic for better validation and reliability.  
  - Removed unsafe inline operations and improved modal handling.  
  - Cleaned redundant event bindings and optimized AJAX calls.

- **CSS and UI cleanup**
  - Complete cleanup of `style.css` and `admin.css` — unified structure and simplified code.  
  - Removed redundant styles, excessive opacity, and hardcoded colors.  
  - All layout variables are now defined with `--lrob-*` CSS variables for Gutenberg integration.  
  - Improved responsive behavior and consistency between admin and frontend.

- **Performance and maintainability**
  - Reduced CSS and JS payloads.  
  - Improved layout rendering logic and block editor previews.  
  - Prepared structure for future Gutenberg variable bindings.
  
### 1.2.1
- **Performance Optimizations**
  - Admin class now loads only in admin context (not on frontend)
  - Settings class no longer loaded on frontend
  - Import/Export class lazy-loads only during actual import/export operations
  - Frontend assets (JS/CSS) load only when block is present on page
  - Admin assets load only on plugin admin pages
  - Reduced memory footprint for non-admin requests

- **Code Quality Improvements**
  - Refactored AJAX handler registration (11 calls → 1 loop)
  - Streamlined submenu registration (4 calls → 1 loop)
  - Added `verify_ajax()` helper for centralized AJAX security checks
  - Added `sanitize_object()` helper to eliminate duplicate sanitization code
  - Removed redundant class requires from admin methods
  - Nullsafe coalescing operator (`??=`) for singleton pattern
  - Total: ~70+ lines reduced through DRY principles

- **Technical Changes**
  - Split admin vs frontend initialization hooks
  - Database class always loads (required for activation hook + blocks)
  - No breaking changes, full backward compatibility

## Todo List
- Make caracteristics such as allergen or badges fully customizable
- Fix the "Unavailable" product's logic (currently doesn't make sense)
- Add other modes for general services with presets (my be used for any kind of service)
- Provide more customization options in Gutenberg editor
