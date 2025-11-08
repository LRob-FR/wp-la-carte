<?php

if (!defined('ABSPATH')) exit;

class LRob_Carte_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'handle_export')); // Handle export before any output
        add_action('wp_ajax_lrob_save_category', array($this, 'ajax_save_category'));
        add_action('wp_ajax_lrob_delete_category', array($this, 'ajax_delete_category'));
        add_action('wp_ajax_lrob_get_category', array($this, 'ajax_get_category'));
        add_action('wp_ajax_lrob_toggle_category', array($this, 'ajax_toggle_category'));
        add_action('wp_ajax_lrob_update_category_hierarchy', array($this, 'ajax_update_category_hierarchy'));
        add_action('wp_ajax_lrob_update_category_parent', array($this, 'ajax_update_category_parent'));
        add_action('wp_ajax_lrob_save_product', array($this, 'ajax_save_product'));
        add_action('wp_ajax_lrob_delete_product', array($this, 'ajax_delete_product'));
        add_action('wp_ajax_lrob_update_positions', array($this, 'ajax_update_positions'));
        add_action('wp_ajax_lrob_get_product', array($this, 'ajax_get_product'));
        add_action('wp_ajax_lrob_create_default_categories', array($this, 'ajax_create_default_categories'));
    }

    public function handle_export() {
        if (!isset($_POST['lrob_export_nonce']) || !wp_verify_nonce($_POST['lrob_export_nonce'], 'lrob_export')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $importer = new LRob_Carte_Import_Export();
        $importer->export();
        // export() method already has exit; inside it
    }

    public function add_menu_pages() {
        add_menu_page(
            __('La Carte', 'lrob-la-carte'),
            __('La Carte', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte',
            array($this, 'render_products_page'),
            'dashicons-food',
            30
        );

        add_submenu_page(
            'lrob-carte',
            __('Products', 'lrob-la-carte'),
            __('Products', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte',
            array($this, 'render_products_page')
        );

        add_submenu_page(
            'lrob-carte',
            __('Categories', 'lrob-la-carte'),
            __('Categories', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte-categories',
            array($this, 'render_categories_page')
        );

        add_submenu_page(
            'lrob-carte',
            __('Settings', 'lrob-la-carte'),
            __('Settings', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'lrob-carte',
            __('Import/Export', 'lrob-la-carte'),
            __('Import/Export', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte-import-export',
            array($this, 'render_import_export_page')
        );
    }

    public function render_products_page() {
        if (!current_user_can('manage_options')) return;

        $categories = LRob_Carte_Database::get_categories('position', 'ASC', true);
        $current_cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';

        // Ne pas charger les produits ici si on est sur "all", on les chargera dans la vue
        if ($current_cat === 'all') {
            $products = array(); // Sera chargé dans la vue
        } else {
            $products = LRob_Carte_Database::get_products_recursive(intval($current_cat));
        }

        include LROB_CARTE_PATH . 'admin/views/products.php';
    }

    public function render_categories_page() {
        if (!current_user_can('manage_options')) return;

        $categories = LRob_Carte_Database::get_categories();
        include LROB_CARTE_PATH . 'admin/views/categories.php';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['lrob_carte_settings_nonce']) && wp_verify_nonce($_POST['lrob_carte_settings_nonce'], 'lrob_carte_settings')) {
            $old_mode = get_option('lrob_carte_mode');
            $new_mode = sanitize_text_field($_POST['mode']);

            update_option('lrob_carte_mode', $new_mode);
            update_option('lrob_carte_primary_color', sanitize_hex_color($_POST['primary_color']));
            update_option('lrob_carte_secondary_color', sanitize_hex_color($_POST['secondary_color']));
            update_option('lrob_carte_out_of_stock_display', sanitize_text_field($_POST['out_of_stock_display']));

            if ($old_mode !== $new_mode) {
                LRob_Carte_Database::add_missing_categories($new_mode);
            }

            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'lrob-la-carte') . '</p></div>';
        }

        if (isset($_POST['lrob_add_categories_nonce']) && wp_verify_nonce($_POST['lrob_add_categories_nonce'], 'lrob_add_categories')) {
            LRob_Carte_Database::add_missing_categories();
            echo '<div class="notice notice-success"><p>' . __('Categories added.', 'lrob-la-carte') . '</p></div>';
        }

        include LROB_CARTE_PATH . 'admin/views/settings.php';
    }

    public function render_import_export_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['lrob_import_nonce']) && wp_verify_nonce($_POST['lrob_import_nonce'], 'lrob_import') && isset($_FILES['import_file'])) {

            // Defensive file checks
            $file = $_FILES['import_file'];
            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                echo '<div class="notice notice-error"><p>' . __('Invalid uploaded file.', 'lrob-la-carte') . '</p></div>';
            } else {
                // Check file extension and mime defensively. Adjust 'json' to whatever importer expects.
                $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
                $ext = isset($filetype['ext']) ? $filetype['ext'] : '';
                $allowed_exts = array('json'); // expected import format(s)
                if (!in_array(strtolower($ext), $allowed_exts, true)) {
                    echo '<div class="notice notice-error"><p>' . __('Invalid file type. Please upload a JSON file.', 'lrob-la-carte') . '</p></div>';
                } else {
                    $importer = new LRob_Carte_Import_Export();
                    $result = $importer->import($file);

                    if ($result['success']) {
                        echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
                    }
                }
            }
        }

        include LROB_CARTE_PATH . 'admin/views/import-export.php';
    }

    public function ajax_save_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['name'])) {
            wp_send_json_error(array('message' => __('Category name is required', 'lrob-la-carte')));
        }

        $data = array(
            'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0,
            'name' => sanitize_text_field($_POST['name']),
            'slug' => !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($_POST['name']),
            'icon_type' => in_array($_POST['icon_type'], array('emoji', 'image')) ? $_POST['icon_type'] : 'emoji',
            'icon_value' => sanitize_text_field($_POST['icon_value']),
            'position' => isset($_POST['position']) ? intval($_POST['position']) : 0,
            'active' => isset($_POST['active']) ? intval($_POST['active']) : 1
        );

        if (isset($_POST['id']) && $_POST['id']) {
            $id = intval($_POST['id']);

            if ($data['parent_id'] == $id) {
                wp_send_json_error(array('message' => __('A category cannot be its own parent', 'lrob-la-carte')));
            }

            $result = LRob_Carte_Database::update_category($id, $data);

            if ($result === false) {
                wp_send_json_error(array('message' => __('Error during update', 'lrob-la-carte')));
            }
        } else {
            $id = LRob_Carte_Database::insert_category($data);

            if (!$id) {
                wp_send_json_error(array('message' => __('Error during creation', 'lrob-la-carte')));
            }
        }

        wp_send_json_success(array('id' => $id, 'message' => __('Category saved', 'lrob-la-carte')));
    }

    public function ajax_delete_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $result = LRob_Carte_Database::delete_category(intval($_POST['id']));

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error during deletion', 'lrob-la-carte')));
        }

        wp_send_json_success(array('message' => __('Category deleted', 'lrob-la-carte')));
    }

    public function ajax_get_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $category = LRob_Carte_Database::get_category(intval($_POST['id']));

        if (!$category) {
            wp_send_json_error(array('message' => __('Category not found', 'lrob-la-carte')));
        }

        // Sanitize returned object before sending it to the client
        $sanitized = array();
        foreach ((array) $category as $k => $v) {
            if (is_numeric($v)) {
                $sanitized[$k] = intval($v);
            } elseif (is_string($v)) {
                $sanitized[$k] = sanitize_text_field($v);
            } else {
                // fallback: convert to string and sanitize
                $sanitized[$k] = is_null($v) ? '' : sanitize_text_field((string) $v);
            }
        }

        wp_send_json_success($sanitized);
    }

    public function ajax_toggle_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $id = intval($_POST['id']);
        $category = LRob_Carte_Database::get_category($id);

        if (!$category) {
            wp_send_json_error(array('message' => __('Category not found', 'lrob-la-carte')));
        }

        $new_status = $category->active ? 0 : 1;
        $result = LRob_Carte_Database::update_category($id, array('active' => $new_status));

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error during update', 'lrob-la-carte')));
        }

        wp_send_json_success(array('message' => __('Status updated', 'lrob-la-carte')));
    }

    public function ajax_update_category_hierarchy() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['updates']) || !is_array($_POST['updates'])) {
            wp_send_json_error(array('message' => __('Invalid data', 'lrob-la-carte')));
        }

        // Defensive cap to avoid huge updates (prevent DoS)
        $updates = $_POST['updates'];
        $max_updates = 500;
        if (count($updates) > $max_updates) {
            wp_send_json_error(array('message' => __('Too many updates', 'lrob-la-carte')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lrob_categories';

        foreach ($updates as $update) {
            if (!is_array($update)) {
                continue;
            }

            // Validate presence of required fields
            if (empty($update['id']) || !isset($update['parent_id']) || !isset($update['position'])) {
                continue;
            }

            $id = intval($update['id']);
            $parent_id = intval($update['parent_id']);
            $position = intval($update['position']);

            // Prevent setting parent to self
            if ($id === $parent_id) {
                continue;
            }

            $wpdb->update(
                $table,
                array(
                    'parent_id' => $parent_id,
                    'position' => $position
                ),
                array('id' => $id),
                array('%d', '%d'),
                array('%d')
            );
        }

        wp_send_json_success(array('message' => __('Hierarchy updated', 'lrob-la-carte')));
    }


    public function ajax_update_category_parent() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (!isset($_POST['id']) || !isset($_POST['parent_id'])) {
            wp_send_json_error(array('message' => __('Invalid data', 'lrob-la-carte')));
        }

        $id = intval($_POST['id']);
        $parent_id = intval($_POST['parent_id']);

        // Prevent a category from being its own parent
        if ($id === $parent_id) {
            wp_send_json_error(array('message' => __('A category cannot be its own parent', 'lrob-la-carte')));
        }

        // Prevent cycles by walking up the parent chain with a depth limit
        global $wpdb;
        $check_parent = $parent_id;
        $depth = 0;
        $max_depth = 50;
        while ($check_parent > 0 && $depth < $max_depth) {
            if ($check_parent === $id) {
                wp_send_json_error(array('message' => __('Cannot create a loop in the category hierarchy', 'lrob-la-carte')));
            }
            $check_parent = $wpdb->get_var($wpdb->prepare(
                "SELECT parent_id FROM {$wpdb->prefix}lrob_categories WHERE id = %d",
                $check_parent
            ));
            $check_parent = $check_parent ? intval($check_parent) : 0;
            $depth++;
        }

        if ($depth >= $max_depth) {
            wp_send_json_error(array('message' => __('Hierarchy too deep or malformed', 'lrob-la-carte')));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'lrob_categories',
            array('parent_id' => $parent_id),
            array('id' => $id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error during update', 'lrob-la-carte')));
        }

        wp_send_json_success(array('message' => __('Parent updated', 'lrob-la-carte')));
    }


    public function ajax_save_product() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['name'])) {
            wp_send_json_error(array('message' => __('Product name is required', 'lrob-la-carte')));
        }

        if (empty($_POST['category_id']) || intval($_POST['category_id']) <= 0) {
            wp_send_json_error(array('message' => __('Invalid category', 'lrob-la-carte')));
        }

        $allowed_availability = array('available', 'out_of_stock', 'temporary');
        $availability = isset($_POST['availability']) && in_array($_POST['availability'], $allowed_availability)
            ? $_POST['availability']
            : 'available';

        $data = array(
            'category_id' => intval($_POST['category_id']),
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'image_id' => isset($_POST['image_id']) ? intval($_POST['image_id']) : 0,
            'allergens' => sanitize_text_field($_POST['allergens'] ?? ''),
            'badges' => sanitize_text_field($_POST['badges'] ?? ''),
            'availability' => $availability,
            'position' => isset($_POST['position']) ? intval($_POST['position']) : 0
        );

        if (isset($_POST['id']) && $_POST['id']) {
            $id = intval($_POST['id']);
            $result = LRob_Carte_Database::update_product($id, $data);

            if ($result === false) {
                wp_send_json_error(array('message' => __('Error during update', 'lrob-la-carte')));
            }
        } else {
            $id = LRob_Carte_Database::insert_product($data);

            if (!$id) {
                wp_send_json_error(array('message' => __('Error during creation', 'lrob-la-carte')));
            }
        }

        if (isset($_POST['prices']) && is_array($_POST['prices'])) {
            $result = LRob_Carte_Database::save_product_prices($id, $_POST['prices']);

            if ($result === false) {
                wp_send_json_error(array('message' => __('Erreur lors de l\'enregistrement des prix', 'lrob-la-carte')));
            }
        }

        wp_send_json_success(array('id' => $id, 'message' => __('Product saved', 'lrob-la-carte')));
    }

    public function ajax_delete_product() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $result = LRob_Carte_Database::delete_product(intval($_POST['id']));

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error during deletion', 'lrob-la-carte')));
        }

        wp_send_json_success(array('message' => __('Product deleted', 'lrob-la-carte')));
    }

    public function ajax_update_positions() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['table']) || empty($_POST['positions'])) {
            wp_send_json_error(array('message' => __('Données invalides', 'lrob-la-carte')));
        }

        $allowed_tables = array('categories', 'products');
        $table = sanitize_text_field($_POST['table']);

        if (!in_array($table, $allowed_tables)) {
            wp_send_json_error(array('message' => __('Table invalide', 'lrob-la-carte')));
        }

        if (!is_array($_POST['positions'])) {
            wp_send_json_error(array('message' => __('Format de positions invalide', 'lrob-la-carte')));
        }

        LRob_Carte_Database::update_positions($table, $_POST['positions']);
        wp_send_json_success(array('message' => __('Positions updated', 'lrob-la-carte')));
    }

    public function ajax_get_product() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $product = LRob_Carte_Database::get_product(intval($_POST['id']));

        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'lrob-la-carte')));
        }

        // Always add image_url property (empty string if no image)
        $product->image_url = '';
        if (!empty($product->image_id) && intval($product->image_id) > 0) {
            $image_url = wp_get_attachment_url($product->image_id);
            if ($image_url) {
                // sanitize URL
                $product->image_url = esc_url_raw($image_url);
            }
        }

        $prices = LRob_Carte_Database::get_product_prices(intval($_POST['id']));

        // Sanitize product fields
        $sanitized_product = array();
        foreach ((array) $product as $k => $v) {
            if ($k === 'image_url') {
                $sanitized_product[$k] = esc_url_raw($v);
                continue;
            }
            if (is_numeric($v)) {
                // keep integers as ints, floats as floats
                if (is_float($v + 0)) {
                    $sanitized_product[$k] = floatval($v);
                } else {
                    $sanitized_product[$k] = intval($v);
                }
            } elseif (is_string($v)) {
                // allow small HTML in description? we sanitize as plain text here for safety
                $sanitized_product[$k] = sanitize_text_field($v);
            } else {
                $sanitized_product[$k] = is_null($v) ? '' : sanitize_text_field((string) $v);
            }
        }

        // Sanitize prices array
        $sanitized_prices = array();
        if (is_array($prices)) {
            foreach ($prices as $price_obj) {
                $p = (array) $price_obj;
                $sanitized_price = array();
                $sanitized_price['id'] = isset($p['id']) ? intval($p['id']) : 0;
                $sanitized_price['label'] = isset($p['label']) ? sanitize_text_field($p['label']) : '';
                $sanitized_price['price'] = isset($p['price']) ? floatval($p['price']) : 0.0;
                $sanitized_price['happy_hour'] = isset($p['happy_hour']) ? intval($p['happy_hour']) : 0;
                $sanitized_price['position'] = isset($p['position']) ? intval($p['position']) : 0;
                $sanitized_prices[] = $sanitized_price;
            }
        }

        wp_send_json_success(array(
            'product' => $sanitized_product,
            'prices' => $sanitized_prices
        ));
    }


    public function ajax_create_default_categories() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }

        $mode = get_option('lrob_carte_mode', 'restaurant');
        LRob_Carte_Database::add_missing_categories($mode);

        wp_send_json_success(array(
            'message' => __('Default categories created successfully', 'lrob-la-carte')
        ));
    }
}
