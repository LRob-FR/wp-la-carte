<div class="wrap lrob-carte-admin">
    <h1><?php esc_html_e('Settings', 'lrob-la-carte'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('lrob_carte_settings', 'lrob_carte_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="mode"><?php esc_html_e('Mode', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <select name="mode" id="mode">
                        <option value="restaurant" <?php selected(get_option('lrob_carte_mode'), 'restaurant'); ?>>
                            <?php esc_html_e('Restaurant', 'lrob-la-carte'); ?>
                        </option>
                        <option value="bar" <?php selected(get_option('lrob_carte_mode'), 'bar'); ?>>
                            <?php esc_html_e('Bar', 'lrob-la-carte'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Defines default categories. Changing mode will automatically add missing categories.', 'lrob-la-carte'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Categories', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <!-- Use a separate form for adding categories -->
                    <form method="post" action="">
                        <?php wp_nonce_field('lrob_add_categories', 'lrob_add_categories_nonce'); ?>
                        <button type="submit" class="button">
                            <?php esc_html_e('Add missing default categories', 'lrob-la-carte'); ?>
                        </button>
                    </form>
                    <p class="description">
                        <?php esc_html_e('Adds missing categories according to current mode without deleting existing categories.', 'lrob-la-carte'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="primary_color"><?php esc_html_e('Primary Color', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <input type="text" name="primary_color" id="primary_color"
                           value="<?php echo esc_attr(get_option('lrob_carte_primary_color', '#2c3e50')); ?>"
                           class="lrob-color-picker">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="secondary_color"><?php esc_html_e('Secondary Color', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <input type="text" name="secondary_color" id="secondary_color"
                           value="<?php echo esc_attr(get_option('lrob_carte_secondary_color', '#e74c3c')); ?>"
                           class="lrob-color-picker">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="out_of_stock_display"><?php esc_html_e('Out of Stock Display', 'lrob-la-carte'); ?></label>
                </th>
                <td>
                    <select name="out_of_stock_display" id="out_of_stock_display">
                        <option value="show" <?php selected(get_option('lrob_carte_out_of_stock_display'), 'show'); ?>>
                            <?php esc_html_e('Show with "Out of Stock" badge', 'lrob-la-carte'); ?>
                        </option>
                        <option value="hide" <?php selected(get_option('lrob_carte_out_of_stock_display'), 'hide'); ?>>
                            <?php esc_html_e('Hide Completely', 'lrob-la-carte'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
