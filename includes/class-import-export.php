<?php

if (!defined('ABSPATH')) exit;

class LRob_Carte_Import_Export {

    public function export() {
        $data = array(
            'version' => LROB_CARTE_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => array(
                'mode' => get_option('lrob_carte_mode'),
                'primary_color' => get_option('lrob_carte_primary_color'),
                'secondary_color' => get_option('lrob_carte_secondary_color'),
                'out_of_stock_display' => get_option('lrob_carte_out_of_stock_display'),
            ),
            'categories' => array(),
            'products' => array()
        );

        $categories = LRob_Carte_Database::get_categories();
        foreach ($categories as $cat) {
            $data['categories'][] = array(
                'name' => (string) $cat->name,
                'slug' => (string) $cat->slug,
                'icon_type' => (string) $cat->icon_type,
                'icon_value' => (string) $cat->icon_value,
                'position' => (int) $cat->position,
                'parent_id' => (int) ($cat->parent_id ?? 0),
                'active' => (int) ($cat->active ?? 1)
            );
        }

        $products = LRob_Carte_Database::get_products();
        foreach ($products as $product) {
            $prices = LRob_Carte_Database::get_product_prices($product->id);
            $product_data = array(
                'category_slug' => $this->get_category_slug($product->category_id),
                'name' => (string) $product->name,
                'description' => (string) ($product->description ?? ''),
                'allergens' => (string) ($product->allergens ?? ''),
                'badges' => (string) ($product->badges ?? ''),
                'availability' => (string) ($product->availability ?? 'available'),
                'position' => (int) ($product->position ?? 0),
                'prices' => array()
            );

            if (!empty($product->image_id)) {
                $image_url = wp_get_attachment_url($product->image_id);
                if ($image_url) {
                    $product_data['image_url'] = esc_url_raw($image_url);
                }
            }

            foreach ($prices as $price) {
                $product_data['prices'][] = array(
                    'label' => (string) $price->label,
                    'price' => (float) $price->price,
                    'happy_hour' => (int) ($price->happy_hour ?? 0)
                );
            }

            $data['products'][] = $product_data;
        }

        // Limit memory blow-up for massive exports
        @set_time_limit(60);
        @ini_set('memory_limit', '256M');

        $filename = 'lrob-carte-export-' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function import($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => __('Upload error.', 'lrob-la-carte'));
        }

        // Basic sanity checks
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            return array('success' => false, 'message' => __('File too large.', 'lrob-la-carte'));
        }

        $ft = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (empty($ft['ext']) || strtolower($ft['ext']) !== 'json') {
            return array('success' => false, 'message' => __('Invalid file type.', 'lrob-la-carte'));
        }

        $json = file_get_contents($file['tmp_name']);
        $data = json_decode($json, true);

        if (!is_array($data) || empty($data['categories']) || empty($data['products'])) {
            return array('success' => false, 'message' => __('Invalid JSON file.', 'lrob-la-carte'));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            if (!empty($data['settings']) && is_array($data['settings'])) {
                foreach ($data['settings'] as $key => $value) {
                    update_option('lrob_carte_' . sanitize_key($key), sanitize_text_field($value));
                }
            }

            $category_map = array();

            foreach ($data['categories'] as $cat_data) {
                if (!is_array($cat_data)) continue;
                $slug = sanitize_title($cat_data['slug']);
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}lrob_categories WHERE slug = %s",
                    $slug
                ));

                $cat_insert_data = array(
                    'name' => sanitize_text_field($cat_data['name'] ?? ''),
                    'slug' => $slug,
                    'icon_type' => sanitize_text_field($cat_data['icon_type'] ?? 'emoji'),
                    'icon_value' => sanitize_text_field($cat_data['icon_value'] ?? '🍽️'),
                    'position' => intval($cat_data['position'] ?? 0),
                    'parent_id' => intval($cat_data['parent_id'] ?? 0),
                    'active' => intval($cat_data['active'] ?? 1)
                );

                if ($existing) {
                    LRob_Carte_Database::update_category($existing, $cat_insert_data);
                    $category_map[$slug] = $existing;
                } else {
                    $new_id = LRob_Carte_Database::insert_category($cat_insert_data);
                    $category_map[$slug] = $new_id;
                }
            }

            foreach ($data['products'] as $prod_data) {
                if (!is_array($prod_data)) continue;
                $slug = sanitize_title($prod_data['category_slug'] ?? '');
                if (empty($slug) || !isset($category_map[$slug])) continue;

                $image_id = 0;
                if (!empty($prod_data['image_url']) && filter_var($prod_data['image_url'], FILTER_VALIDATE_URL)) {
                    $image_id = $this->import_image($prod_data['image_url']);
                }

                $product_data = array(
                    'category_id' => intval($category_map[$slug]),
                    'name' => sanitize_text_field($prod_data['name'] ?? ''),
                    'description' => sanitize_textarea_field($prod_data['description'] ?? ''),
                    'image_id' => $image_id,
                    'allergens' => sanitize_text_field($prod_data['allergens'] ?? ''),
                    'badges' => sanitize_text_field($prod_data['badges'] ?? ''),
                    'availability' => sanitize_text_field($prod_data['availability'] ?? 'available'),
                    'position' => intval($prod_data['position'] ?? 0)
                );

                $product_id = LRob_Carte_Database::insert_product($product_data);

                if (!empty($prod_data['prices']) && is_array($prod_data['prices'])) {
                    // Defensive: cap number of price entries
                    $limited_prices = array_slice($prod_data['prices'], 0, 100);
                    LRob_Carte_Database::save_product_prices($product_id, $limited_prices);
                }
            }

            $wpdb->query('COMMIT');
            return array('success' => true, 'message' => __('Import successful!', 'lrob-la-carte'));

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return array('success' => false, 'message' => __('Import error: ', 'lrob-la-carte') . esc_html($e->getMessage()));
        }
    }

    private function get_category_slug($category_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT slug FROM {$wpdb->prefix}lrob_categories WHERE id = %d",
            $category_id
        ));
    }

    private function import_image($url) {
        // Only allow HTTP(S)
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower($scheme), array('http', 'https'), true)) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url, 30); // 30s timeout
        if (is_wp_error($tmp)) {
            return 0;
        }

        // Reject if file too large (>5MB)
        if (filesize($tmp) > 5 * 1024 * 1024) {
            @unlink($tmp);
            return 0;
        }

        $file_array = array(
            'name' => sanitize_file_name(basename($url)),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0);
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return 0;
        }

        return (int) $id;
    }

}
