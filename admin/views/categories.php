<div class="wrap lrob-carte-admin">
    <h1>
        <?php esc_html_e('Categories', 'lrob-la-carte'); ?>
        <button class="button button-primary" id="lrob-add-category">
            <?php esc_html_e('Add Category', 'lrob-la-carte'); ?>
        </button>
        <?php if (empty($categories)) : ?>
            <button class="button button-secondary" id="lrob-create-default-categories" style="margin-left: 10px;">
                <?php esc_html_e('Create Default Categories', 'lrob-la-carte'); ?>
            </button>
        <?php endif; ?>
    </h1>

    <div id="lrob-categories-list" class="lrob-sortable">
        <?php
        // Organize categories by hierarchy
        $categories_by_parent = array();
        foreach ($categories as $cat) {
            $parent_id = $cat->parent_id ?? 0;
            if (!isset($categories_by_parent[$parent_id])) {
                $categories_by_parent[$parent_id] = array();
            }
            $categories_by_parent[$parent_id][] = $cat;
        }

        // Recursive closure to display hierarchy
        $display_category_tree = function ($parent_id, $categories_by_parent, $level = 0) use (&$display_category_tree) {
            if (!isset($categories_by_parent[$parent_id])) {
                return;
            }

            foreach ($categories_by_parent[$parent_id] as $cat) :
                $indent_class = $level > 0 ? 'lrob-category-child level-' . $level : '';
        ?>
            <div class="lrob-category-item <?php echo esc_attr($indent_class); ?> <?php echo $cat->active ? '' : 'lrob-inactive'; ?>"
                 data-id="<?php echo esc_attr($cat->id); ?>"
                 data-parent="<?php echo esc_attr($cat->parent_id ?? 0); ?>"
                 data-level="<?php echo esc_attr($level); ?>">

                <div class="lrob-category-order-buttons">
                    <button class="button button-small lrob-move-category-up" title="<?php esc_attr_e('Move Up', 'lrob-la-carte'); ?>">↑</button>
                    <button class="button button-small lrob-move-category-down" title="<?php esc_attr_e('Move Down', 'lrob-la-carte'); ?>">↓</button>
                </div>

                <?php if ($level > 0) : ?>
                    <div class="lrob-category-indent">
                        <?php echo esc_html(str_repeat('└─ ', $level)); ?>
                    </div>
                <?php endif; ?>

                <div class="lrob-category-icon">
                    <?php
                    if ($cat->icon_type === 'emoji') {
                        echo esc_html($cat->icon_value);
                    } elseif ($cat->icon_type === 'image' && $cat->icon_value) {
                        echo wp_get_attachment_image(intval($cat->icon_value), array(24, 24));
                    } else {
                        echo '<i class="' . esc_attr($cat->icon_value) . '"></i>';
                    }
                    ?>
                </div>

                <div class="lrob-category-info">
                    <div class="lrob-category-name">
                        <?php echo esc_html($cat->name); ?>
                        <?php if (!$cat->active) : ?>
                            <span class="lrob-badge lrob-badge-inactive"><?php esc_html_e('Disabled', 'lrob-la-carte'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lrob-category-slug"><?php echo esc_html($cat->slug); ?></div>
                </div>

                <div class="lrob-category-actions">
                    <?php if ($level > 0) : ?>
                        <button class="button button-small lrob-unindent-category"
                                data-id="<?php echo esc_attr($cat->id); ?>"
                                title="<?php esc_attr_e('Move up one level', 'lrob-la-carte'); ?>">
                            ← <?php esc_html_e('Parent level', 'lrob-la-carte'); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($level < 2) : // Max 3 levels ?>
                        <button class="button button-small lrob-indent-category"
                                data-id="<?php echo esc_attr($cat->id); ?>"
                                title="<?php esc_attr_e('Move down one level', 'lrob-la-carte'); ?>">
                            → <?php esc_html_e('Subcategory', 'lrob-la-carte'); ?>
                        </button>
                    <?php endif; ?>

                    <button class="button lrob-toggle-category"
                            data-id="<?php echo esc_attr($cat->id); ?>"
                            data-active="<?php echo esc_attr($cat->active); ?>">
                        <?php echo $cat->active ? esc_html__('Disable', 'lrob-la-carte') : esc_html__('Enable', 'lrob-la-carte'); ?>
                    </button>

                    <button class="button lrob-edit-category" data-id="<?php echo esc_attr($cat->id); ?>">
                        <?php esc_html_e('Edit', 'lrob-la-carte'); ?>
                    </button>

                    <button class="button lrob-delete-category" data-id="<?php echo esc_attr($cat->id); ?>">
                        <?php esc_html_e('Delete', 'lrob-la-carte'); ?>
                    </button>
                </div>
            </div>
        <?php
                // Recursive call
                $display_category_tree($cat->id, $categories_by_parent, $level + 1);
            endforeach;
        };

        // Display from root categories (parent_id = 0)
        $display_category_tree(0, $categories_by_parent);
        ?>
    </div>
</div>

<div id="lrob-category-modal" class="lrob-modal" style="display:none;">
    <div class="lrob-modal-content lrob-modal-small">
        <span class="lrob-modal-close">&times;</span>
        <h2 id="lrob-category-modal-title"><?php esc_html_e('Add Category', 'lrob-la-carte'); ?></h2>

        <form id="lrob-category-form">
            <input type="hidden" id="category-id" value="">
            <input type="hidden" id="category-position" value="999">

            <table class="form-table">
                <tr>
                    <th><label for="category-name"><?php esc_html_e('Name', 'lrob-la-carte'); ?> *</label></th>
                    <td><input type="text" id="category-name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="category-slug"><?php esc_html_e('Slug', 'lrob-la-carte'); ?></label></th>
                    <td><input type="text" id="category-slug" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="category-parent"><?php esc_html_e('Parent Category', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <select id="category-parent" class="regular-text">
                            <option value="0"><?php esc_html_e('None (root category)', 'lrob-la-carte'); ?></option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->id); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Create a category hierarchy (e.g. Cocktails under Drinks)', 'lrob-la-carte'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="category-active"><?php esc_html_e('Status', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="category-active" value="1" checked>
                            <?php esc_html_e('Active category (visible on site)', 'lrob-la-carte'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Icon Type', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <label>
                            <input type="radio" name="icon-type" value="emoji" checked> <?php esc_html_e('Emoji', 'lrob-la-carte'); ?>
                        </label>
                        <label>
                            <input type="radio" name="icon-type" value="image"> <?php esc_html_e('Custom Image', 'lrob-la-carte'); ?>
                        </label>
                    </td>
                </tr>
                <tr id="icon-emoji-row">
                    <th><label><?php esc_html_e('Emoji', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <div class="lrob-emoji-picker">
                            <?php foreach (LRob_Carte_Settings::get_emoji_presets() as $emoji) : ?>
                                <span class="lrob-emoji-option" data-emoji="<?php echo esc_attr($emoji); ?>">
                                    <?php echo esc_html($emoji); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" id="category-emoji" value="🍽️" maxlength="2">
                    </td>
                </tr>
                <tr id="icon-image-row" style="display:none;">
                    <th><label><?php esc_html_e('Custom Image', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <input type="hidden" id="category-icon-image-id" value="">
                        <div id="category-icon-image-preview" style="margin-bottom: 10px;"></div>
                        <button type="button" class="button" id="category-upload-icon-image">
                            <?php esc_html_e('Choose Image', 'lrob-la-carte'); ?>
                        </button>
                        <button type="button" class="button" id="category-remove-icon-image" style="display:none;">
                            <?php esc_html_e('Remove', 'lrob-la-carte'); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e('Recommended format: 64x64px, PNG with transparent background', 'lrob-la-carte'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'lrob-la-carte'); ?></button>
                <button type="button" class="button lrob-modal-close"><?php esc_html_e('Cancel', 'lrob-la-carte'); ?></button>
            </p>
        </form>
    </div>
</div>
