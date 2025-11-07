# LRob - La Carte

A professional menu management plugin for bars and restaurants on WordPress.

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

## Installation

1. Download the latest release ZIP
2. Upload to WordPress via Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Go to Menu → Categories to create your first categories

## Usage

### Creating Categories

1. Navigate to **Menu → Categories**
2. Click **"Create Default Categories"** for a quick start, or
3. Click **"Add Category"** to create custom categories
4. Organize with drag & drop, add icons (emoji, image, or Font Awesome)

### Adding Products

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

- **WordPress Version**: 5.0+
- **PHP Version**: 7.4+
- **Text Domain**: `lrob-la-carte`
- **Database Tables**: 
  - `wp_lrob_categories`
  - `wp_lrob_products`
  - `wp_lrob_product_prices`

## Support

For support, please visit [LRob](https://www.lrob.fr/)

## Credits

**Developed by [LRob, Hébergeur web spécialiste WordPress](https://www.lrob.fr/)**  

## License

This plugin is proprietary software developed by LRob.

## Changelog

### 1.0.0
- Initial release
- Complete English translation with i18n support
- French translation included
- Smart category creation (user-controlled, language-aware)
- Professional release build system
- No breaking changes from 1.x
