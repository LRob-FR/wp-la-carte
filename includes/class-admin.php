<?php

if (!defined('ABSPATH')) exit;

class LRob_Carte_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
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
        add_action('wp_ajax_lrob_download_fontawesome', array($this, 'ajax_download_fontawesome'));
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
            __('Produits', 'lrob-la-carte'),
            __('Produits', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte',
            array($this, 'render_products_page')
        );

        add_submenu_page(
            'lrob-carte',
            __('Catégories', 'lrob-la-carte'),
            __('Catégories', 'lrob-la-carte'),
            'manage_options',
            'lrob-carte-categories',
            array($this, 'render_categories_page')
        );

        add_submenu_page(
            'lrob-carte',
            __('Réglages', 'lrob-la-carte'),
            __('Réglages', 'lrob-la-carte'),
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
            
            echo '<div class="notice notice-success"><p>' . __('Réglages enregistrés.', 'lrob-la-carte') . '</p></div>';
        }

        if (isset($_POST['lrob_add_categories_nonce']) && wp_verify_nonce($_POST['lrob_add_categories_nonce'], 'lrob_add_categories')) {
            LRob_Carte_Database::add_missing_categories();
            echo '<div class="notice notice-success"><p>' . __('Catégories ajoutées.', 'lrob-la-carte') . '</p></div>';
        }

        include LROB_CARTE_PATH . 'admin/views/settings.php';
    }

    public function render_import_export_page() {
        if (!current_user_can('manage_options')) return;
        
        $importer = new LRob_Carte_Import_Export();
        
        if (isset($_POST['lrob_export_nonce']) && wp_verify_nonce($_POST['lrob_export_nonce'], 'lrob_export')) {
            $importer->export();
            exit;
        }

        if (isset($_POST['lrob_import_nonce']) && wp_verify_nonce($_POST['lrob_import_nonce'], 'lrob_import') && isset($_FILES['import_file'])) {
            $result = $importer->import($_FILES['import_file']);
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
            }
        }

        include LROB_CARTE_PATH . 'admin/views/import-export.php';
    }

    public function ajax_save_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['name'])) {
            wp_send_json_error(array('message' => __('Le nom de la catégorie est requis', 'lrob-la-carte')));
        }

        $data = array(
            'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0,
            'name' => sanitize_text_field($_POST['name']),
            'slug' => !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($_POST['name']),
            'icon_type' => in_array($_POST['icon_type'], array('emoji', 'fontawesome')) ? $_POST['icon_type'] : 'emoji',
            'icon_value' => sanitize_text_field($_POST['icon_value']),
            'position' => isset($_POST['position']) ? intval($_POST['position']) : 0,
            'active' => isset($_POST['active']) ? intval($_POST['active']) : 1
        );

        if (isset($_POST['id']) && $_POST['id']) {
            $id = intval($_POST['id']);
            
            if ($data['parent_id'] == $id) {
                wp_send_json_error(array('message' => __('Une catégorie ne peut pas être son propre parent', 'lrob-la-carte')));
            }
            
            $result = LRob_Carte_Database::update_category($id, $data);
            
            if ($result === false) {
                wp_send_json_error(array('message' => __('Erreur lors de la mise à jour', 'lrob-la-carte')));
            }
        } else {
            $id = LRob_Carte_Database::insert_category($data);
            
            if (!$id) {
                wp_send_json_error(array('message' => __('Erreur lors de la création', 'lrob-la-carte')));
            }
        }

        wp_send_json_success(array('id' => $id, 'message' => __('Catégorie enregistrée', 'lrob-la-carte')));
    }

    public function ajax_delete_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID invalide', 'lrob-la-carte')));
        }

        $result = LRob_Carte_Database::delete_category(intval($_POST['id']));
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Erreur lors de la suppression', 'lrob-la-carte')));
        }
        
        wp_send_json_success(array('message' => __('Catégorie supprimée', 'lrob-la-carte')));
    }

    public function ajax_get_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID invalide', 'lrob-la-carte')));
        }

        $category = LRob_Carte_Database::get_category(intval($_POST['id']));
        
        if (!$category) {
            wp_send_json_error(array('message' => __('Catégorie introuvable', 'lrob-la-carte')));
        }
        
        wp_send_json_success($category);
    }

    public function ajax_toggle_category() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID invalide', 'lrob-la-carte')));
        }

        global $wpdb;
        $id = intval($_POST['id']);
        $current_active = intval($_POST['active']);
        $new_active = $current_active ? 0 : 1;
        
        // Debug : vérifier que la colonne existe
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}lrob_categories LIKE 'active'");
        if (empty($columns)) {
            wp_send_json_error(array('message' => __('La colonne "active" n\'existe pas. Veuillez désactiver puis réactiver le plugin.', 'lrob-la-carte')));
        }
        
        // Vérifier que la catégorie existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}lrob_categories WHERE id = %d",
            $id
        ));
        
        if (!$exists) {
            wp_send_json_error(array('message' => __('Catégorie introuvable (ID: ' . $id . ')', 'lrob-la-carte')));
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'lrob_categories',
            array('active' => $new_active),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
        
        // $result peut être 0 (aucune ligne modifiée car valeur identique) ou false (erreur SQL)
        if ($result === false) {
            wp_send_json_error(array('message' => __('Erreur SQL: ', 'lrob-la-carte') . $wpdb->last_error));
        }
        
        wp_send_json_success(array(
            'message' => $new_active ? __('Catégorie activée', 'lrob-la-carte') : __('Catégorie désactivée', 'lrob-la-carte'),
            'new_active' => $new_active,
            'rows_affected' => $result
        ));
    }

    public function ajax_update_category_hierarchy() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['updates']) || !is_array($_POST['updates'])) {
            wp_send_json_error(array('message' => __('Données invalides', 'lrob-la-carte')));
        }

        global $wpdb;
        
        foreach ($_POST['updates'] as $update) {
            $wpdb->update(
                $wpdb->prefix . 'lrob_categories',
                array(
                    'parent_id' => intval($update['parent_id']),
                    'position' => intval($update['position'])
                ),
                array('id' => intval($update['id'])),
                array('%d', '%d'),
                array('%d')
            );
        }
        
        wp_send_json_success(array('message' => __('Hiérarchie mise à jour', 'lrob-la-carte')));
    }

    public function ajax_update_category_parent() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (!isset($_POST['id']) || !isset($_POST['parent_id'])) {
            wp_send_json_error(array('message' => __('Données invalides', 'lrob-la-carte')));
        }

        $id = intval($_POST['id']);
        $parent_id = intval($_POST['parent_id']);
        
        // Empêcher qu'une catégorie soit son propre parent
        if ($id === $parent_id) {
            wp_send_json_error(array('message' => __('Une catégorie ne peut pas être son propre parent', 'lrob-la-carte')));
        }
        
        // Empêcher les boucles (vérifier que parent_id n'est pas un descendant de id)
        global $wpdb;
        $check_parent = $parent_id;
        while ($check_parent > 0) {
            if ($check_parent === $id) {
                wp_send_json_error(array('message' => __('Impossible de créer une boucle dans la hiérarchie', 'lrob-la-carte')));
            }
            $check_parent = $wpdb->get_var($wpdb->prepare(
                "SELECT parent_id FROM {$wpdb->prefix}lrob_categories WHERE id = %d",
                $check_parent
            ));
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'lrob_categories',
            array('parent_id' => $parent_id),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Erreur lors de la mise à jour', 'lrob-la-carte')));
        }
        
        wp_send_json_success(array('message' => __('Parent mis à jour', 'lrob-la-carte')));
    }

    public function ajax_save_product() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['name'])) {
            wp_send_json_error(array('message' => __('Le nom du produit est requis', 'lrob-la-carte')));
        }

        if (empty($_POST['category_id']) || intval($_POST['category_id']) <= 0) {
            wp_send_json_error(array('message' => __('Catégorie invalide', 'lrob-la-carte')));
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
                wp_send_json_error(array('message' => __('Erreur lors de la mise à jour', 'lrob-la-carte')));
            }
        } else {
            $id = LRob_Carte_Database::insert_product($data);
            
            if (!$id) {
                wp_send_json_error(array('message' => __('Erreur lors de la création', 'lrob-la-carte')));
            }
        }

        if (isset($_POST['prices']) && is_array($_POST['prices'])) {
            $result = LRob_Carte_Database::save_product_prices($id, $_POST['prices']);
            
            if ($result === false) {
                wp_send_json_error(array('message' => __('Erreur lors de l\'enregistrement des prix', 'lrob-la-carte')));
            }
        }

        wp_send_json_success(array('id' => $id, 'message' => __('Produit enregistré', 'lrob-la-carte')));
    }

    public function ajax_delete_product() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID invalide', 'lrob-la-carte')));
        }

        $result = LRob_Carte_Database::delete_product(intval($_POST['id']));
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Erreur lors de la suppression', 'lrob-la-carte')));
        }
        
        wp_send_json_success(array('message' => __('Produit supprimé', 'lrob-la-carte')));
    }

    public function ajax_update_positions() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
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
        wp_send_json_success(array('message' => __('Positions mises à jour', 'lrob-la-carte')));
    }

    public function ajax_get_product() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non autorisé', 'lrob-la-carte')));
        }

        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID invalide', 'lrob-la-carte')));
        }

        $product = LRob_Carte_Database::get_product(intval($_POST['id']));
        
        if (!$product) {
            wp_send_json_error(array('message' => __('Produit introuvable', 'lrob-la-carte')));
        }
        
        $prices = LRob_Carte_Database::get_product_prices(intval($_POST['id']));

        wp_send_json_success(array(
            'product' => $product,
            'prices' => $prices
        ));
    }

    public function ajax_download_fontawesome() {
        check_ajax_referer('lrob_carte_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
        global $wp_filesystem;

        $fa_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
        $fa_dir = LROB_CARTE_PATH . 'assets/fontawesome/css/';
        $fa_webfonts_dir = LROB_CARTE_PATH . 'assets/fontawesome/webfonts/';

        if (!file_exists($fa_dir)) {
            wp_mkdir_p($fa_dir);
        }
        if (!file_exists($fa_webfonts_dir)) {
            wp_mkdir_p($fa_webfonts_dir);
        }

        $css_content = wp_remote_retrieve_body(wp_remote_get($fa_url));
        
        if (is_wp_error($css_content) || empty($css_content)) {
            wp_send_json_error('Failed to download Font Awesome CSS');
        }

        $css_content = str_replace('../webfonts/', '../webfonts/', $css_content);
        $wp_filesystem->put_contents($fa_dir . 'all.min.css', $css_content, FS_CHMOD_FILE);

        preg_match_all('/url\((\.\.\/webfonts\/[^\)]+)\)/', $css_content, $matches);
        
        if (!empty($matches[1])) {
            $base_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/';
            
            foreach (array_unique($matches[1]) as $font_path) {
                $font_file = basename($font_path);
                $font_url = $base_url . $font_file;
                
                $font_content = wp_remote_retrieve_body(wp_remote_get($font_url));
                
                if (!is_wp_error($font_content) && !empty($font_content)) {
                    $wp_filesystem->put_contents($fa_webfonts_dir . $font_file, $font_content, FS_CHMOD_FILE);
                }
            }
        }

        update_option('lrob_carte_load_fontawesome', true);

        wp_send_json_success('Font Awesome downloaded successfully');
    }
}
