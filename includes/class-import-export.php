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
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon_type' => $cat->icon_type,
                'icon_value' => $cat->icon_value,
                'position' => $cat->position
            );
        }

        $products = LRob_Carte_Database::get_products();
        
        foreach ($products as $product) {
            $prices = LRob_Carte_Database::get_product_prices($product->id);
            
            $product_data = array(
                'category_slug' => $this->get_category_slug($product->category_id),
                'name' => $product->name,
                'description' => $product->description,
                'allergens' => $product->allergens,
                'badges' => $product->badges,
                'availability' => $product->availability,
                'position' => $product->position,
                'prices' => array()
            );

            if ($product->image_id) {
                $image_url = wp_get_attachment_url($product->image_id);
                if ($image_url) {
                    $product_data['image_url'] = $image_url;
                }
            }

            foreach ($prices as $price) {
                $product_data['prices'][] = array(
                    'label' => $price->label,
                    'price' => $price->price
                );
            }

            $data['products'][] = $product_data;
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lrob-carte-export-' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function import($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => __('Erreur lors du téléchargement du fichier.', 'lrob-la-carte'));
        }

        $json = file_get_contents($file['tmp_name']);
        $data = json_decode($json, true);

        if (!$data || !isset($data['categories']) || !isset($data['products'])) {
            return array('success' => false, 'message' => __('Fichier JSON invalide.', 'lrob-la-carte'));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            if (isset($data['settings'])) {
                foreach ($data['settings'] as $key => $value) {
                    update_option('lrob_carte_' . $key, $value);
                }
            }

            $category_map = array();

            foreach ($data['categories'] as $cat_data) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}lrob_categories WHERE slug = %s",
                    $cat_data['slug']
                ));

                if ($existing) {
                    LRob_Carte_Database::update_category($existing, $cat_data);
                    $category_map[$cat_data['slug']] = $existing;
                } else {
                    $new_id = LRob_Carte_Database::insert_category($cat_data);
                    $category_map[$cat_data['slug']] = $new_id;
                }
            }

            foreach ($data['products'] as $prod_data) {
                if (!isset($category_map[$prod_data['category_slug']])) {
                    continue;
                }

                $image_id = 0;
                if (isset($prod_data['image_url'])) {
                    $image_id = $this->import_image($prod_data['image_url']);
                }

                $product_data = array(
                    'category_id' => $category_map[$prod_data['category_slug']],
                    'name' => $prod_data['name'],
                    'description' => $prod_data['description'] ?? '',
                    'image_id' => $image_id,
                    'allergens' => $prod_data['allergens'] ?? '',
                    'badges' => $prod_data['badges'] ?? '',
                    'availability' => $prod_data['availability'] ?? 'available',
                    'position' => $prod_data['position'] ?? 0
                );

                $product_id = LRob_Carte_Database::insert_product($product_data);

                if (isset($prod_data['prices']) && is_array($prod_data['prices'])) {
                    LRob_Carte_Database::save_product_prices($product_id, $prod_data['prices']);
                }
            }

            $wpdb->query('COMMIT');
            return array('success' => true, 'message' => __('Import réussi !', 'lrob-la-carte'));

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return array('success' => false, 'message' => __('Erreur lors de l\'import : ', 'lrob-la-carte') . $e->getMessage());
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
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);
        
        if (is_wp_error($tmp)) {
            return 0;
        }

        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return 0;
        }

        return $id;
    }
}
