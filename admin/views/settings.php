<div class="wrap lrob-carte-admin">
    <h1><?php _e('Settings', 'lrob-la-carte'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('lrob_carte_settings', 'lrob_carte_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="mode"><?php _e('Mode', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <select name="mode" id="mode">
                        <option value="restaurant" <?php selected(get_option('lrob_carte_mode'), 'restaurant'); ?>>
                            <?php _e('Restaurant', 'lrob-la-carte'); ?>
                        </option>
                        <option value="bar" <?php selected(get_option('lrob_carte_mode'), 'bar'); ?>>
                            <?php _e('Bar', 'lrob-la-carte'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Defines default categories. Changing mode will automatically add missing categories.', 'lrob-la-carte'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php _e('Categories', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <form method="post" action="" style="display:inline;">
                        <?php wp_nonce_field('lrob_add_categories', 'lrob_add_categories_nonce'); ?>
                        <button type="submit" class="button">
                            <?php _e('Add missing default categories', 'lrob-la-carte'); ?>
                        </button>
                    </form>
                    <p class="description">
                        <?php _e('Adds missing categories according to current mode without deleting existing categories.', 'lrob-la-carte'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="primary_color"><?php _e('Primary Color', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <input type="text" name="primary_color" id="primary_color"
                           value="<?php echo esc_attr(get_option('lrob_carte_primary_color', '#2c3e50')); ?>"
                           class="lrob-color-picker">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="secondary_color"><?php _e('Secondary Color', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <input type="text" name="secondary_color" id="secondary_color"
                           value="<?php echo esc_attr(get_option('lrob_carte_secondary_color', '#e74c3c')); ?>"
                           class="lrob-color-picker">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="out_of_stock_display"><?php _e('Out of Stock Display', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <select name="out_of_stock_display" id="out_of_stock_display">
                        <option value="show" <?php selected(get_option('lrob_carte_out_of_stock_display'), 'show'); ?>>
                            <?php _e('Show with "Out of Stock" badge', 'lrob-la-carte'); ?>
                        </option>
                        <option value="hide" <?php selected(get_option('lrob_carte_out_of_stock_display'), 'hide'); ?>>
                            <?php _e('Hide Completely', 'lrob-la-carte'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php _e('Font Awesome', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <?php if (get_option('lrob_carte_load_fontawesome', false)): ?>
                        <p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Font Awesome is installed', 'lrob-la-carte'); ?></p>
                    <?php else: ?>
                        <button type="button" class="button" id="lrob-download-fa">
                            <?php _e('Download Font Awesome', 'lrob-la-carte'); ?>
                        </button>
                        <p class="description">
                            <?php _e('Required if you use Font Awesome icons for categories.', 'lrob-la-carte'); ?>
                        </p>
                        <div id="lrob-fa-status" style="margin-top: 10px;"></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
