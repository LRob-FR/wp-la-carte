<?php

if (!defined('ABSPATH')) exit;

class LRob_Carte_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lrob_categories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            parent_id bigint(20) DEFAULT 0,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            icon_type varchar(20) DEFAULT 'emoji',
            icon_value varchar(100) DEFAULT '🍽️',
            position int(11) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id)
            ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lrob_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            category_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            image_id bigint(20),
            allergens text,
            badges text,
            availability varchar(20) DEFAULT 'available',
            position int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id)
            ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lrob_product_prices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            label varchar(100),
            price decimal(10,2) NOT NULL,
            happy_hour tinyint(1) DEFAULT 0,
            position int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }

        self::insert_default_categories();
        self::set_default_settings();
    }

    public static function migrate_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lrob_categories';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        if (!$table_exists) {
            // Table doesn't exist yet, it will be created by create_tables
            return;
        }

        // Check and add parent_id if missing
        $parent_id_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'parent_id'");
        if (empty($parent_id_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN parent_id bigint(20) DEFAULT 0 AFTER id");
            $wpdb->query("ALTER TABLE {$table_name} ADD KEY parent_id (parent_id)");
        }

        // Check and add active if missing
        $active_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'active'");
        if (empty($active_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN active tinyint(1) DEFAULT 1 AFTER position");
        }

        // Update all existing rows to ensure active = 1 by default
        $wpdb->query("UPDATE {$table_name} SET active = 1 WHERE active IS NULL OR active = 0");

        // Add happy_hour to prices table
        $prices_table = $wpdb->prefix . 'lrob_product_prices';
        $happy_hour_exists = $wpdb->get_results("SHOW COLUMNS FROM {$prices_table} LIKE 'happy_hour'");
        if (empty($happy_hour_exists)) {
            $wpdb->query("ALTER TABLE {$prices_table} ADD COLUMN happy_hour tinyint(1) DEFAULT 0 AFTER price");
        }
    }

    private static function insert_default_categories() {
        // Do nothing on activation
        // Categories will be created manually via the admin interface button
        return;
    }

    private static function set_default_settings() {
        $defaults = array(
            'lrob_carte_mode' => 'restaurant',
            'lrob_carte_primary_color' => '#2c3e50',
            'lrob_carte_secondary_color' => '#e74c3c',
            'lrob_carte_out_of_stock_display' => 'show',
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    public static function get_categories($orderby = 'position', $order = 'ASC', $active_only = false) {
        global $wpdb;

        $allowed_orderby = array('position', 'name', 'id', 'created_at');
        $allowed_order = array('ASC', 'DESC');

        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'position';
        }

        if (!in_array(strtoupper($order), $allowed_order)) {
            $order = 'ASC';
        }

        $where = $active_only ? "WHERE active = 1" : "";

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}lrob_categories {$where} ORDER BY {$orderby} {$order}"
        );
    }

    public static function get_category($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrob_categories WHERE id = %d",
            $id
        ));
    }

    public static function insert_category($data) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'lrob_categories',
            array(
                'parent_id' => isset($data['parent_id']) ? intval($data['parent_id']) : 0,
                  'name' => sanitize_text_field($data['name']),
                  'slug' => sanitize_title($data['slug']),
                  'icon_type' => sanitize_text_field($data['icon_type']),
                  'icon_value' => sanitize_text_field($data['icon_value']),
                  'position' => intval($data['position']),
                  'active' => isset($data['active']) ? intval($data['active']) : 1
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );

        return $wpdb->insert_id;
    }

    public static function update_category($id, $data) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'lrob_categories',
            array(
                'parent_id' => isset($data['parent_id']) ? intval($data['parent_id']) : 0,
                  'name' => sanitize_text_field($data['name']),
                  'slug' => sanitize_title($data['slug']),
                  'icon_type' => sanitize_text_field($data['icon_type']),
                  'icon_value' => sanitize_text_field($data['icon_value']),
                  'position' => intval($data['position']),
                  'active' => isset($data['active']) ? intval($data['active']) : 1
            ),
            array('id' => $id),
                             array('%d', '%s', '%s', '%s', '%s', '%d', '%d'),
                             array('%d')
        );
    }

    public static function delete_category($id) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'lrob_products', array('category_id' => $id), array('%d'));
        return $wpdb->delete($wpdb->prefix . 'lrob_categories', array('id' => $id), array('%d'));
    }

    public static function get_products($category_id = null) {
        global $wpdb;

        if ($category_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lrob_products WHERE category_id = %d ORDER BY position ASC",
                $category_id
            ));
        }

        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lrob_products ORDER BY position ASC");
    }

    public static function get_product($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrob_products WHERE id = %d",
            $id
        ));
    }

    public static function insert_product($data) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'lrob_products',
            array(
                'category_id' => intval($data['category_id']),
                  'name' => sanitize_text_field($data['name']),
                  'description' => sanitize_textarea_field($data['description']),
                  'image_id' => intval($data['image_id']),
                  'allergens' => sanitize_text_field($data['allergens']),
                  'badges' => sanitize_text_field($data['badges']),
                  'availability' => sanitize_text_field($data['availability']),
                  'position' => intval($data['position'])
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
        );

        return $wpdb->insert_id;
    }

    public static function update_product($id, $data) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'lrob_products',
            array(
                'category_id' => intval($data['category_id']),
                  'name' => sanitize_text_field($data['name']),
                  'description' => sanitize_textarea_field($data['description']),
                  'image_id' => intval($data['image_id']),
                  'allergens' => sanitize_text_field($data['allergens']),
                  'badges' => sanitize_text_field($data['badges']),
                  'availability' => sanitize_text_field($data['availability']),
                  'position' => intval($data['position'])
            ),
            array('id' => $id),
                             array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d'),
                             array('%d')
        );
    }

    public static function delete_product($id) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'lrob_product_prices', array('product_id' => $id), array('%d'));
        return $wpdb->delete($wpdb->prefix . 'lrob_products', array('id' => $id), array('%d'));
    }

    public static function get_product_prices($product_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrob_product_prices WHERE product_id = %d ORDER BY position ASC",
            $product_id
        ));
    }

    public static function save_product_prices($product_id, $prices) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'lrob_product_prices', array('product_id' => $product_id), array('%d'));

        if (!is_array($prices)) {
            return false;
        }

        foreach ($prices as $index => $price) {
            if (!empty($price['price'])) {
                $price_value = floatval($price['price']);

                if ($price_value < 0) {
                    continue;
                }

                $wpdb->insert(
                    $wpdb->prefix . 'lrob_product_prices',
                    array(
                        'product_id' => intval($product_id),
                          'label' => sanitize_text_field($price['label'] ?? ''),
                          'price' => $price_value,
                          'happy_hour' => isset($price['happy_hour']) ? intval($price['happy_hour']) : 0,
                          'position' => intval($index)
                    ),
                    array('%d', '%s', '%f', '%d', '%d')
                );
            }
        }

        return true;
    }

    public static function update_positions($table, $positions) {
        global $wpdb;

        foreach ($positions as $index => $id) {
            $wpdb->update(
                $wpdb->prefix . 'lrob_' . $table,
                array('position' => $index),
                          array('id' => intval($id)),
                          array('%d'),
                          array('%d')
            );
        }
    }

    public static function add_missing_categories($mode = null) {
        global $wpdb;

        if ($mode === null) {
            $mode = get_option('lrob_carte_mode', 'restaurant');
        }

        $restaurant_cats = array(
            array('name' => __('Starters', 'lrob-la-carte'), 'icon' => '🥗', 'pos' => 10),
                                 array('name' => __('Main Courses', 'lrob-la-carte'), 'icon' => '🍽️', 'pos' => 20),
                                 array('name' => __('Burgers', 'lrob-la-carte'), 'icon' => '🍔', 'pos' => 30),
                                 array('name' => __('Pizzas', 'lrob-la-carte'), 'icon' => '🍕', 'pos' => 40),
                                 array('name' => __('Pasta', 'lrob-la-carte'), 'icon' => '🍝', 'pos' => 50),
                                 array('name' => __('Desserts', 'lrob-la-carte'), 'icon' => '🍰', 'pos' => 60),
                                 array('name' => __('Drinks', 'lrob-la-carte'), 'icon' => '🥤', 'pos' => 70),
        );

        $bar_cats = array(
            array('name' => __('Beers', 'lrob-la-carte'), 'icon' => '🍺', 'pos' => 10),
                          array('name' => __('Wines', 'lrob-la-carte'), 'icon' => '🍷', 'pos' => 20),
                          array('name' => __('Cocktails', 'lrob-la-carte'), 'icon' => '🍹', 'pos' => 30),
                          array('name' => __('Spirits', 'lrob-la-carte'), 'icon' => '🥃', 'pos' => 40),
                          array('name' => __('Aperitifs', 'lrob-la-carte'), 'icon' => '🥂', 'pos' => 50),
                          array('name' => __('Digestifs', 'lrob-la-carte'), 'icon' => '🍸', 'pos' => 60),
                          array('name' => __('Soft Drinks', 'lrob-la-carte'), 'icon' => '🥤', 'pos' => 70),
                          array('name' => __('Coffee & Tea', 'lrob-la-carte'), 'icon' => '☕', 'pos' => 80),
                          array('name' => __('Snacks', 'lrob-la-carte'), 'icon' => '🍟', 'pos' => 90),
        );

        $categories = ($mode === 'bar') ? $bar_cats : $restaurant_cats;

        foreach ($categories as $cat) {
            $slug = sanitize_title($cat['name']);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}lrob_categories WHERE slug = %s",
                $slug
            ));

            if (!$exists) {
                $wpdb->insert(
                    $wpdb->prefix . 'lrob_categories',
                    array(
                        'name' => $cat['name'],
                        'slug' => $slug,
                        'icon_type' => 'emoji',
                        'icon_value' => $cat['icon'],
                        'position' => $cat['pos']
                    ),
                    array('%s', '%s', '%s', '%s', '%d')
                );
            }
        }
    }

    // Helper to build hierarchical category tree
    public static function get_categories_tree($active_only = false) {
        $all_categories = self::get_categories('position', 'ASC', $active_only);

        $tree = array();
        $indexed = array();

        // Index by ID
        foreach ($all_categories as $cat) {
            $cat->children = array();
            $indexed[$cat->id] = $cat;
        }

        // Build tree
        foreach ($indexed as $cat) {
            if ($cat->parent_id && isset($indexed[$cat->parent_id])) {
                $indexed[$cat->parent_id]->children[] = $cat;
            } else {
                $tree[] = $cat;
            }
        }

        return $tree;
    }

    // Helper to get all products from a category and its children
    public static function get_products_recursive($category_id) {
        global $wpdb;

        $category_ids = array($category_id);
        $children = self::get_child_categories($category_id);

        foreach ($children as $child) {
            $category_ids[] = $child->id;
        }

        $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrob_products
            WHERE category_id IN ($placeholders)
        ORDER BY position ASC",
        ...$category_ids
        ));
    }

    // Helper to get subcategories
    public static function get_child_categories($parent_id, $recursive = true) {
        global $wpdb;

        $children = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lrob_categories
            WHERE parent_id = %d AND active = 1
            ORDER BY position ASC",
            $parent_id
        ));

        if ($recursive) {
            $all_children = $children;
            foreach ($children as $child) {
                $sub_children = self::get_child_categories($child->id, true);
                $all_children = array_merge($all_children, $sub_children);
            }
            return $all_children;
        }

        return $children;
    }
}
