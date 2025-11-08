<div class="wrap lrob-carte-admin">
    <h1><?php _e('Products', 'lrob-la-carte'); ?></h1>

    <?php if (empty($categories)): ?>
        <div class="notice notice-warning">
            <p><?php _e('No categories found. Please create categories first.', 'lrob-la-carte'); ?></p>
        </div>
    <?php else: ?>

        <?php
        // Navigation breadcrumb
        $breadcrumb_cats = array();
        if ($current_cat && $current_cat !== 'all') {
            $cat = LRob_Carte_Database::get_category($current_cat);
            if ($cat) {
                $breadcrumb_cats[] = $cat;
                $parent_id = $cat->parent_id;
                while ($parent_id > 0) {
                    $parent = LRob_Carte_Database::get_category($parent_id);
                    if ($parent) {
                        array_unshift($breadcrumb_cats, $parent);
                        $parent_id = $parent->parent_id;
                    } else {
                        break;
                    }
                }
            }
        }
        ?>

        <div class="lrob-breadcrumb">
            <a href="?page=lrob-carte&cat=all" class="lrob-breadcrumb-item <?php echo $current_cat === 'all' ? 'active' : ''; ?>">
                📁 <?php _e('All Categories', 'lrob-la-carte'); ?>
            </a>
            <?php foreach ($breadcrumb_cats as $bc): ?>
                <span class="lrob-breadcrumb-separator">›</span>
                <a href="?page=lrob-carte&cat=<?php echo $bc->id; ?>" class="lrob-breadcrumb-item <?php echo $current_cat == $bc->id ? 'active' : ''; ?>">
                    <?php echo esc_html($bc->name); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="lrob-products-header">
            <div class="lrob-search-box">
                <input type="text" id="lrob-product-search" placeholder="<?php _e('Search for a product...', 'lrob-la-carte'); ?>" class="regular-text">
                <span class="lrob-search-count"></span>
            </div>
            <button class="button button-primary" id="lrob-add-product">
                <?php _e('Add Product', 'lrob-la-carte'); ?>
            </button>
        </div>

        <div id="lrob-products-grid">
            <?php
            // 1. FIRST display subcategories
            if ($current_cat === 'all') {
                $child_categories = array_filter($categories, function($cat) {
                    return !$cat->parent_id || $cat->parent_id == 0;
                });
            } else {
                $child_categories = array_filter($categories, function($cat) use ($current_cat) {
                    return $cat->parent_id == $current_cat;
                });
            }

            if (!empty($child_categories)):
                foreach ($child_categories as $child_cat):
                    $child_products_count = count(LRob_Carte_Database::get_products_recursive($child_cat->id));
            ?>
                <a href="?page=lrob-carte&cat=<?php echo $child_cat->id; ?>" class="lrob-category-card">
                    <div class="lrob-category-card-icon">
                        <?php
                        if ($child_cat->icon_type === 'emoji') {
                            echo esc_html($child_cat->icon_value);
                        } elseif ($child_cat->icon_type === 'image' && $child_cat->icon_value) {
                            echo wp_get_attachment_image($child_cat->icon_value, array(48, 48));
                        } else {
                            echo '<i class="' . esc_attr($child_cat->icon_value) . '"></i>';
                        }
                        ?>
                    </div>
                    <div class="lrob-category-card-name"><?php echo esc_html($child_cat->name); ?></div>
                    <div class="lrob-category-card-count">
                        <?php
                        printf(
                            _n('%s product', '%s products', $child_products_count, 'lrob-la-carte'),
                            $child_products_count
                        );
                        ?>
                    </div>
                </a>
            <?php
                endforeach;
            endif;
            ?>
        </div>

        <?php
        // 2. THEN display products (if in a category OR if on "all")
        if (!empty($products) || $current_cat === 'all'):
            // If on "all", get all products
            if ($current_cat === 'all') {
                $products = LRob_Carte_Database::get_products();
            }
        ?>
            <?php if (!empty($products)): ?>
                <h2 style="margin-top: 30px; margin-bottom: 15px; font-size: 18px;">
                    <?php
                    if ($current_cat === 'all') {
                        _e('All Products', 'lrob-la-carte');
                    } else {
                        _e('Products', 'lrob-la-carte');
                    }
                    ?>
                </h2>
                <div id="lrob-products-grid">
                    <?php
                    foreach ($products as $product):
                        $prices = LRob_Carte_Database::get_product_prices($product->id);
                        $availability_class = $product->availability !== 'available' ? 'lrob-unavailable' : '';
                        $product_category = LRob_Carte_Database::get_category($product->category_id);
                    ?>
                        <div class="lrob-product-card <?php echo $availability_class; ?>" data-id="<?php echo $product->id; ?>" data-category-id="<?php echo $product->category_id; ?>">
                            <?php if ($product->image_id): ?>
                                <div class="lrob-product-card-image">
                                    <?php echo wp_get_attachment_image($product->image_id, 'thumbnail'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="lrob-product-card-content">
                                <div class="lrob-product-card-header">
                                    <div class="lrob-product-card-name">
                                        <?php echo esc_html($product->name); ?>
                                    </div>
                                    <div class="lrob-product-card-badges">
                                        <?php if ($product_category): ?>
                                            <span class="lrob-badge lrob-badge-category" title="<?php echo esc_attr($product_category->name); ?>">
                                                <?php echo esc_html($product_category->name); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($product->availability !== 'available'): ?>
                                            <span class="lrob-badge lrob-badge-unavailable"><?php _e('Out of Stock', 'lrob-la-carte'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($product->badges):
                                            $badges = explode(',', $product->badges);
                                            foreach ($badges as $badge): ?>
                                                <span class="lrob-badge lrob-badge-<?php echo esc_attr($badge); ?>">
                                                    <?php echo esc_html(LRob_Carte_Settings::get_badges()[$badge] ?? $badge); ?>
                                                </span>
                                            <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>

                                <?php if ($product->description): ?>
                                    <div class="lrob-product-card-description">
                                        <?php echo esc_html($product->description); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($prices)): ?>
                                    <div class="lrob-product-card-prices">
                                        <?php foreach ($prices as $price): ?>
                                            <span class="lrob-price">
                                                <?php if ($price->label): ?>
                                                    <span class="lrob-price-label"><?php echo esc_html($price->label); ?>:</span>
                                                <?php endif; ?>
                                                <span class="lrob-price-amount"><?php echo number_format($price->price, 2); ?>€</span>
                                                <?php if ($price->is_happy_hour): ?>
                                                    <span class="lrob-price-happy-badge">🍹</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($product->allergens): ?>
                                    <div class="lrob-product-card-allergens">
                                        <small>⚠️ <?php echo esc_html($product->allergens); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="lrob-product-card-actions">
                                <button class="button button-small lrob-edit-product" data-id="<?php echo $product->id; ?>">
                                    ✏️
                                </button>
                                <button class="button button-small lrob-delete-product" data-id="<?php echo $product->id; ?>">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($child_categories) && empty($products)): ?>
            <p class="lrob-empty"><?php _e('No products or subcategories here.', 'lrob-la-carte'); ?></p>
        <?php endif; ?>

    <?php endif; ?>
</div>

<div id="lrob-product-modal" class="lrob-modal" style="display:none;">
    <div class="lrob-modal-content">
        <span class="lrob-modal-close">&times;</span>
        <h2 id="lrob-modal-title"><?php _e('Add Product', 'lrob-la-carte'); ?></h2>

        <form id="lrob-product-form">
            <input type="hidden" id="product-id" value="">
            <input type="hidden" id="product-category" value="<?php echo $current_cat !== 'all' ? $current_cat : ''; ?>">
            <input type="hidden" id="product-position" value="999">
            <input type="hidden" id="product-mode-hidden" value="<?php echo esc_attr(get_option('lrob_carte_mode', 'restaurant')); ?>">

            <table class="form-table">
                <tr>
                    <th><label for="product-category-select"><?php _e('Category', 'lrob-la-carte'); ?> *</label></th>
                    <td>
                        <select id="product-category-select" class="regular-text" required>
                            <option value=""><?php _e('Choose a category', 'lrob-la-carte'); ?></option>
                            <?php
                            $categories_tree = LRob_Carte_Database::get_categories_tree(true);
                            function render_category_options($categories, $selected_id, $level = 0) {
                                foreach ($categories as $cat):
                                    $indent = str_repeat('└─ ', $level);
                            ?>
                                <option value="<?php echo $cat->id; ?>" <?php selected($selected_id, $cat->id); ?>>
                                    <?php echo $indent . esc_html($cat->name); ?>
                                </option>
                            <?php
                                    if (!empty($cat->children)) {
                                        render_category_options($cat->children, $selected_id, $level + 1);
                                    }
                                endforeach;
                            }
                            render_category_options($categories_tree, $current_cat !== 'all' ? $current_cat : 0);
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-name"><?php _e('Product Name', 'lrob-la-carte'); ?> *</label></th>
                    <td><input type="text" id="product-name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="product-description"><?php _e('Description', 'lrob-la-carte'); ?></label></th>
                    <td><textarea id="product-description" class="large-text" rows="3"></textarea></td>
                </tr>
                <tr>
                    <th><label><?php _e('Image', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <div id="product-image-preview"></div>
                        <input type="hidden" id="product-image-id" value="0">
                        <button type="button" class="button" id="product-upload-image"><?php _e('Choose Image', 'lrob-la-carte'); ?></button>
                        <button type="button" class="button" id="product-remove-image" style="display:none;"><?php _e('Remove', 'lrob-la-carte'); ?></button>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Price', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <p class="description"><?php _e('Label (optional): specify a variant (e.g. "Glass (12cl)", "Pint", "Bottle"). Previously used labels will be suggested automatically.', 'lrob-la-carte'); ?></p>
                        <div id="product-prices-wrapper">
                            <div class="lrob-price-row">
                                <input type="text" class="price-label" placeholder="<?php _e('E.g. Glass (12cl)', 'lrob-la-carte'); ?>" list="price-labels-suggestions">
                                <input type="number" class="price-amount" placeholder="0.00" step="0.01" min="0">
                                <button type="button" class="button lrob-remove-price">−</button>
                            </div>
                        </div>
                        <button type="button" class="button" id="lrob-add-price"><?php _e('Add Price', 'lrob-la-carte'); ?></button>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Allergens', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <div class="lrob-checkboxes">
                            <?php foreach (LRob_Carte_Settings::get_allergens() as $key => $label): ?>
                                <label>
                                    <input type="checkbox" class="allergen-checkbox" value="<?php echo $key; ?>">
                                    <?php echo $label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Badges', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <div class="lrob-checkboxes">
                            <?php foreach (LRob_Carte_Settings::get_badges() as $key => $label): ?>
                                <label>
                                    <input type="checkbox" class="badge-checkbox" value="<?php echo $key; ?>">
                                    <?php echo $label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-availability"><?php _e('Availability', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <select id="product-availability">
                            <option value="available"><?php _e('Available', 'lrob-la-carte'); ?></option>
                            <option value="unavailable"><?php _e('Out of Stock', 'lrob-la-carte'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Save', 'lrob-la-carte'); ?></button>
                <button type="button" class="button lrob-modal-close"><?php _e('Cancel', 'lrob-la-carte'); ?></button>
            </p>
        </form>
    </div>
</div>
