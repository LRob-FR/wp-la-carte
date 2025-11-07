<?php
/**
 * Plugin Name: LRob - La Carte
 * Plugin URI: https://www.lrob.fr/
 * Description: Menu manager for bars and restaurants
 * Version: 1.0.0
 * Author: LRob
 * Author URI: https://www.lrob.fr/
 * Text Domain: lrob-la-carte
 */

if (!defined('ABSPATH')) exit;

define('LROB_CARTE_VERSION', '1.0.0');
define('LROB_CARTE_PATH', plugin_dir_path(__FILE__));
define('LROB_CARTE_URL', plugin_dir_url(__FILE__));

require_once LROB_CARTE_PATH . 'includes/class-database.php';
require_once LROB_CARTE_PATH . 'includes/class-admin.php';
require_once LROB_CARTE_PATH . 'includes/class-settings.php';
require_once LROB_CARTE_PATH . 'includes/class-import-export.php';

class LRob_La_Carte {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, array('LRob_Carte_Database', 'create_tables'));

        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'check_database_version'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
    }

    public function check_database_version() {
        $db_version = get_option('lrob_carte_db_version', '0');
        $current_version = '1.2'; // Version with happy_hour

        if (version_compare($db_version, $current_version, '<')) {
            LRob_Carte_Database::migrate_database();
            update_option('lrob_carte_db_version', $current_version);
        }
    }

    public function init() {
        if (is_admin()) {
            new LRob_Carte_Admin();
        }

        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        register_block_type(
            LROB_CARTE_PATH . 'blocks/menu-display',
            array(
                'render_callback' => array($this, 'render_menu_block')
            )
        );
    }

    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'lrob-carte-block-editor',
            LROB_CARTE_URL . 'blocks/menu-display/index.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
                          LROB_CARTE_VERSION
        );

        $categories = LRob_Carte_Database::get_categories();
        $categories_options = array(array('label' => __('Choose a category', 'lrob-la-carte'), 'value' => 0));

        foreach ($categories as $cat) {
            $categories_options[] = array(
                'label' => $cat->name,
                'value' => (int) $cat->id
            );
        }

        wp_localize_script('lrob-carte-block-editor', 'lrobCarteEditor', array(
            'categories' => $categories_options
        ));

        wp_enqueue_style(
            'lrob-carte-block-editor-style',
            LROB_CARTE_URL . 'blocks/menu-display/style.css',
            array(),
                         LROB_CARTE_VERSION
        );
    }

    public function render_menu_block($attributes) {
        ob_start();
        include LROB_CARTE_PATH . 'blocks/menu-display/render.php';
        return ob_get_clean();
    }

    public function admin_assets($hook) {
        if (strpos($hook, 'lrob-carte') === false) return;

        if (get_option('lrob_carte_load_fontawesome', false)) {
            wp_enqueue_style('font-awesome', LROB_CARTE_URL . 'assets/fontawesome/css/all.min.css', array(), '6.5.1');
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_style('lrob-carte-admin', LROB_CARTE_URL . 'admin/css/admin.css', array(), LROB_CARTE_VERSION);
        wp_enqueue_script('lrob-carte-admin', LROB_CARTE_URL . 'admin/js/admin.js', array('jquery', 'wp-color-picker', 'jquery-ui-sortable'), LROB_CARTE_VERSION, true);

        wp_localize_script('lrob-carte-admin', 'lrobCarte', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
                                                                  'nonce' => wp_create_nonce('lrob_carte_nonce')
        ));
    }

    public function frontend_assets() {
        if (has_block('lrob-carte/menu-display')) {
            if (get_option('lrob_carte_load_fontawesome', false)) {
                wp_enqueue_style('font-awesome', LROB_CARTE_URL . 'assets/fontawesome/css/all.min.css', array(), '6.5.1');
            }

            wp_enqueue_style('lrob-carte-frontend', LROB_CARTE_URL . 'blocks/menu-display/style.css', array(), LROB_CARTE_VERSION);
            wp_enqueue_script('lrob-carte-frontend', LROB_CARTE_URL . 'assets/js/frontend.js', array(), LROB_CARTE_VERSION, true);
        }
    }
}

LRob_La_Carte::instance();
