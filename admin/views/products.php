<div class="wrap lrob-carte-admin">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;"><?php _e('Products', 'lrob-la-carte'); ?></h1>
        <button class="button button-primary" id="lrob-add-product">
            <?php _e('Add Product', 'lrob-la-carte'); ?>
        </button>
    </div>

    <?php if (empty($categories)): ?>
        <div class="notice notice-warning">
            <p><?php _e('No categories found. Please create categories first.', 'lrob-la-carte'); ?></p>
        </div>
    <?php else: ?>

        <?php
        // Build categories hierarchy for filtering
        $categories_by_parent = array();
        foreach ($categories as $cat) {
            if (!$cat) continue;
            $parent_id = $cat->parent_id ?? 0;
            if (!isset($categories_by_parent[$parent_id])) {
                $categories_by_parent[$parent_id] = array();
            }
            $categories_by_parent[$parent_id][] = $cat;
        }

        // Get all products for display
        $all_products = LRob_Carte_Database::get_products();

        // Organize products by root category for filtering
        $root_categories = $categories_by_parent[0] ?? array();
        ?>

        <?php if (!empty($root_categories)): ?>
            <!-- Root category navigation (horizontal tabs) -->
            <div class="lrob-category-tabs" data-admin-filter-wrapper>
                <?php foreach ($root_categories as $root_cat):
                    $root_products = LRob_Carte_Database::get_products_recursive($root_cat->id);
                    if (empty($root_products)) continue;
                ?>
                    <button class="lrob-tab lrob-admin-root-tab" data-root-category="<?php echo $root_cat->id; ?>">
                        <span class="lrob-cat-icon">
                            <?php
                            if ($root_cat->icon_type === 'emoji') {
                                echo esc_html($root_cat->icon_value);
                            } elseif ($root_cat->icon_type === 'image' && $root_cat->icon_value) {
                                echo wp_get_attachment_image($root_cat->icon_value, array(24, 24));
                            }
                            ?>
                        </span>
                        <?php echo esc_html($root_cat->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Level 1 subcategory filters (shown for selected root category) -->
            <?php foreach ($root_categories as $root_cat):
                if (!isset($categories_by_parent[$root_cat->id]) || empty($categories_by_parent[$root_cat->id])) continue;

                $has_products_in_children = false;
                foreach ($categories_by_parent[$root_cat->id] as $child_cat) {
                    $child_products = LRob_Carte_Database::get_products_recursive($child_cat->id);
                    if (!empty($child_products)) {
                        $has_products_in_children = true;
                        break;
                    }
                }
                if (!$has_products_in_children) continue;
            ?>
                <div class="lrob-subcategory-filters lrob-level-1-filters"
                     data-parent-id="<?php echo $root_cat->id; ?>"
                     data-filter-level="1"
                     style="display: none; margin: 15px 0; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($categories_by_parent[$root_cat->id] as $child_cat):
                        $child_products = LRob_Carte_Database::get_products_recursive($child_cat->id);
                        if (empty($child_products)) continue;
                    ?>
                        <button class="lrob-subcategory-badge"
                                data-subcategory-id="<?php echo $child_cat->id; ?>"
                                data-parent-id="<?php echo $root_cat->id; ?>"
                                data-filter-level="1">
                            <span class="lrob-subcategory-badge-icon">
                                <?php
                                if ($child_cat->icon_type === 'emoji') {
                                    echo esc_html($child_cat->icon_value);
                                } elseif ($child_cat->icon_type === 'image' && $child_cat->icon_value) {
                                    echo wp_get_attachment_image($child_cat->icon_value, array(16, 16));
                                }
                                ?>
                            </span>
                            <?php echo esc_html($child_cat->name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- Level 2 subcategory filters (shown when level 1 is selected) -->
            <?php foreach ($root_categories as $root_cat):
                if (!isset($categories_by_parent[$root_cat->id])) continue;
                foreach ($categories_by_parent[$root_cat->id] as $child_cat):
                    if (!isset($categories_by_parent[$child_cat->id]) || empty($categories_by_parent[$child_cat->id])) continue;

                    $has_products_in_grandchildren = false;
                    foreach ($categories_by_parent[$child_cat->id] as $grandchild_cat) {
                        $grandchild_products = LRob_Carte_Database::get_products_recursive($grandchild_cat->id);
                        if (!empty($grandchild_products)) {
                            $has_products_in_grandchildren = true;
                            break;
                        }
                    }
                    if (!$has_products_in_grandchildren) continue;
            ?>
                <div class="lrob-subcategory-filters lrob-level-2-filters"
                     data-parent-id="<?php echo $child_cat->id; ?>"
                     data-filter-level="2"
                     style="display: none; margin: 15px 0; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($categories_by_parent[$child_cat->id] as $grandchild_cat):
                        $grandchild_products = LRob_Carte_Database::get_products_recursive($grandchild_cat->id);
                        if (empty($grandchild_products)) continue;
                    ?>
                        <button class="lrob-subcategory-badge"
                                data-subcategory-id="<?php echo $grandchild_cat->id; ?>"
                                data-parent-id="<?php echo $child_cat->id; ?>"
                                data-filter-level="2">
                            <span class="lrob-subcategory-badge-icon">
                                <?php
                                if ($grandchild_cat->icon_type === 'emoji') {
                                    echo esc_html($grandchild_cat->icon_value);
                                } elseif ($grandchild_cat->icon_type === 'image' && $grandchild_cat->icon_value) {
                                    echo wp_get_attachment_image($grandchild_cat->icon_value, array(16, 16));
                                }
                                ?>
                            </span>
                            <?php echo esc_html($grandchild_cat->name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; endforeach; ?>
        <?php endif; ?>

        <!-- Products display -->
        <?php if (!empty($all_products)): ?>
            <h2 style="margin-top: 30px; margin-bottom: 15px; font-size: 18px;">
                <?php _e('Products', 'lrob-la-carte'); ?>
            </h2>
            <div id="lrob-products-grid">
                <?php
                foreach ($all_products as $product):
                    $prices = LRob_Carte_Database::get_product_prices($product->id);
                    $availability_class = $product->availability !== 'available' ? 'lrob-unavailable' : '';
                    $product_category = LRob_Carte_Database::get_category($product->category_id);

                    // Get all ancestor category IDs for this product
                    $ancestor_ids = array($product->category_id);
                    $current_parent_id = $product_category ? $product_category->parent_id : 0;
                    while ($current_parent_id > 0) {
                        $ancestor_ids[] = $current_parent_id;
                        $parent_cat = LRob_Carte_Database::get_category($current_parent_id);
                        $current_parent_id = $parent_cat ? $parent_cat->parent_id : 0;
                    }
                ?>
                    <div class="lrob-product-card <?php echo $availability_class; ?>"
                         data-id="<?php echo $product->id; ?>"
                         data-category-id="<?php echo $product->category_id; ?>"
                         data-category-ancestors="<?php echo implode(',', $ancestor_ids); ?>">

                        <div class="lrob-product-card-content">
                            <div class="lrob-product-card-header">
                                <!-- Left column: Name + Image + Description -->
                                <div class="lrob-product-card-main">
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

                                    <div class="lrob-product-card-image-desc">
                                        <?php if ($product->image_id): ?>
                                            <div class="lrob-product-card-image">
                                                <?php echo wp_get_attachment_image($product->image_id, 'thumbnail'); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($product->description): ?>
                                            <div class="lrob-product-card-desc">
                                                <?php echo esc_html($product->description); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Right column: Prices -->
                                <?php if (!empty($prices)): ?>
                                    <div class="lrob-product-card-prices">
                                        <?php foreach ($prices as $price): ?>
                                            <span class="lrob-price <?php echo $price->happy_hour ? 'lrob-price-happy' : ''; ?>">
                                                <?php if ($price->label): ?>
                                                    <span class="lrob-price-label"><?php echo esc_html($price->label); ?></span>
                                                <?php endif; ?>
                                                <strong><?php echo number_format($price->price, 2, ',', ' '); ?> €</strong>
                                                <?php if ($price->is_happy_hour): ?>
                                                    <span class="lrob-price-happy-badge">🍹</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

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
        <?php else: ?>
            <p class="lrob-empty"><?php _e('No products found.', 'lrob-la-carte'); ?></p>
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
                        <div id="product-image-preview" style="margin-bottom: 10px;"></div>
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
