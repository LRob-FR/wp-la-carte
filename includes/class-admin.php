<?php

if (!defined('ABSPATH')) exit;

class LRob_Carte_Admin {

    public function __construct() {
        // Load settings class (needed in multiple views)
        require_once LROB_CARTE_PATH . 'includes/class-settings.php';

        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'handle_export'));

        // AJAX handlers
        $ajax_actions = array(
            'save_category', 'delete_category', 'get_category', 'toggle_category',
            'update_category_hierarchy', 'update_category_parent', 'save_product',
            'delete_product', 'update_positions', 'get_product', 'create_default_categories'
        );

        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_lrob_{$action}", array($this, "ajax_{$action}"));
        }
    }

    private function load_import_export() {
        if (!class_exists('LRob_Carte_Import_Export')) {
            require_once LROB_CARTE_PATH . 'includes/class-import-export.php';
        }
    }

    private function verify_ajax() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'lrob-la-carte')));
        }
    }

    private function sanitize_object($object) {
        $sanitized = array();
        foreach ((array) $object as $k => $v) {
            if ($k === 'image_url') {
                $sanitized[$k] = esc_url_raw($v);
            } elseif (is_numeric($v)) {
                $sanitized[$k] = is_float($v + 0) ? floatval($v) : intval($v);
            } elseif (is_string($v)) {
                $sanitized[$k] = sanitize_text_field($v);
            } else {
                $sanitized[$k] = is_null($v) ? '' : sanitize_text_field((string) $v);
            }
        }
        return $sanitized;
    }

    public function handle_export() {
        if (!isset($_POST['lrob_export_nonce']) || !wp_verify_nonce($_POST['lrob_export_nonce'], 'lrob_export')) {
            return;
        }

        if (!current_user_can('manage_options')) return;

        $this->load_import_export();
        $importer = new LRob_Carte_Import_Export();
        $importer->export();
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

        $submenus = array(
            array('lrob-carte', __('Products', 'lrob-la-carte'), 'render_products_page'),
            array('lrob-carte-categories', __('Categories', 'lrob-la-carte'), 'render_categories_page'),
            array('lrob-carte-settings', __('Settings', 'lrob-la-carte'), 'render_settings_page'),
            array('lrob-carte-import-export', __('Import/Export', 'lrob-la-carte'), 'render_import_export_page')
        );

        foreach ($submenus as $submenu) {
            add_submenu_page('lrob-carte', $submenu[1], $submenu[1], 'manage_options', $submenu[0], array($this, $submenu[2]));
        }
    }

    public function render_products_page() {
        if (!current_user_can('manage_options')) return;

        $categories = LRob_Carte_Database::get_categories('position', 'ASC', true);
        $current_cat = $_GET['cat'] ?? 'all';
        $products = ($current_cat === 'all') ? array() : LRob_Carte_Database::get_products_recursive(intval($current_cat));

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
            $file = $_FILES['import_file'];

            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                echo '<div class="notice notice-error"><p>' . __('Invalid uploaded file.', 'lrob-la-carte') . '</p></div>';
            } else {
                $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
                $ext = $filetype['ext'] ?? '';

                if (!in_array(strtolower($ext), array('json'), true)) {
                    echo '<div class="notice notice-error"><p>' . __('Invalid file type. Please upload a JSON file.', 'lrob-la-carte') . '</p></div>';
                } else {
                    $this->load_import_export();
                    $importer = new LRob_Carte_Import_Export();
                    $result = $importer->import($file);

                    $notice_type = $result['success'] ? 'success' : 'error';
                    echo '<div class="notice notice-' . $notice_type . '"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        include LROB_CARTE_PATH . 'admin/views/import-export.php';
    }

    public function ajax_save_category() {
        $this->verify_ajax();

        if (empty($_POST['name'])) {
            wp_send_json_error(array('message' => __('Category name is required', 'lrob-la-carte')));
        }

        $data = array(
            'parent_id' => intval($_POST['parent_id'] ?? 0),
            'name' => sanitize_text_field($_POST['name']),
            'slug' => !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($_POST['name']),
            'icon_type' => in_array($_POST['icon_type'], array('emoji', 'image')) ? $_POST['icon_type'] : 'emoji',
            'icon_value' => sanitize_text_field($_POST['icon_value']),
            'position' => intval($_POST['position'] ?? 0),
            'active' => intval($_POST['active'] ?? 1)
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
        $this->verify_ajax();

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
        $this->verify_ajax();

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $category = LRob_Carte_Database::get_category(intval($_POST['id']));

        if (!$category) {
            wp_send_json_error(array('message' => __('Category not found', 'lrob-la-carte')));
        }

        wp_send_json_success($this->sanitize_object($category));
    }

    public function ajax_toggle_category() {
        $this->verify_ajax();

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
        $this->verify_ajax();

        if (empty($_POST['updates']) || !is_array($_POST['updates'])) {
            wp_send_json_error(array('message' => __('Invalid data', 'lrob-la-carte')));
        }

        $updates = $_POST['updates'];
        if (count($updates) > 500) {
            wp_send_json_error(array('message' => __('Too many updates', 'lrob-la-carte')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lrob_categories';

        foreach ($updates as $update) {
            if (!is_array($update) || empty($update['id']) || !isset($update['parent_id']) || !isset($update['position'])) {
                continue;
            }

            $id = intval($update['id']);
            $parent_id = intval($update['parent_id']);
            $position = intval($update['position']);

            if ($id === $parent_id) continue;

            $wpdb->update(
                $table,
                array('parent_id' => $parent_id, 'position' => $position),
                array('id' => $id),
                array('%d', '%d'),
                array('%d')
            );
        }

        wp_send_json_success(array('message' => __('Hierarchy updated', 'lrob-la-carte')));
    }

    public function ajax_update_category_parent() {
        $this->verify_ajax();

        if (!isset($_POST['id']) || !isset($_POST['parent_id'])) {
            wp_send_json_error(array('message' => __('Invalid data', 'lrob-la-carte')));
        }

        $id = intval($_POST['id']);
        $parent_id = intval($_POST['parent_id']);

        if ($id === $parent_id) {
            wp_send_json_error(array('message' => __('A category cannot be its own parent', 'lrob-la-carte')));
        }

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
        $this->verify_ajax();

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
            'image_id' => intval($_POST['image_id'] ?? 0),
            'allergens' => sanitize_text_field($_POST['allergens'] ?? ''),
            'badges' => sanitize_text_field($_POST['badges'] ?? ''),
            'availability' => $availability,
            'position' => intval($_POST['position'] ?? 0)
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
        $this->verify_ajax();

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
        $this->verify_ajax();

        if (empty($_POST['table']) || empty($_POST['positions'])) {
            wp_send_json_error(array('message' => __('Données invalides', 'lrob-la-carte')));
        }

        $table = sanitize_text_field($_POST['table']);

        if (!in_array($table, array('categories', 'products'))) {
            wp_send_json_error(array('message' => __('Table invalide', 'lrob-la-carte')));
        }

        if (!is_array($_POST['positions'])) {
            wp_send_json_error(array('message' => __('Format de positions invalide', 'lrob-la-carte')));
        }

        LRob_Carte_Database::update_positions($table, $_POST['positions']);
        wp_send_json_success(array('message' => __('Positions updated', 'lrob-la-carte')));
    }

    public function ajax_get_product() {
        $this->verify_ajax();

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Invalid ID', 'lrob-la-carte')));
        }

        $product = LRob_Carte_Database::get_product(intval($_POST['id']));

        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'lrob-la-carte')));
        }

        $product->image_url = '';
        if (!empty($product->image_id) && intval($product->image_id) > 0) {
            $image_url = wp_get_attachment_url($product->image_id);
            if ($image_url) {
                $product->image_url = esc_url_raw($image_url);
            }
        }

        $prices = LRob_Carte_Database::get_product_prices(intval($_POST['id']));

        $sanitized_prices = array();
        if (is_array($prices)) {
            foreach ($prices as $price_obj) {
                $p = (array) $price_obj;
                $sanitized_prices[] = array(
                    'id' => intval($p['id'] ?? 0),
                    'label' => sanitize_text_field($p['label'] ?? ''),
                    'price' => floatval($p['price'] ?? 0.0),
                    'happy_hour' => intval($p['happy_hour'] ?? 0),
                    'position' => intval($p['position'] ?? 0)
                );
            }
        }

        wp_send_json_success(array(
            'product' => $this->sanitize_object($product),
            'prices' => $sanitized_prices
        ));
    }

    public function ajax_create_default_categories() {
        $this->verify_ajax();

        $mode = get_option('lrob_carte_mode', 'restaurant');
        LRob_Carte_Database::add_missing_categories($mode);

        wp_send_json_success(array(
            'message' => __('Default categories created successfully', 'lrob-la-carte')
        ));
    }
}
