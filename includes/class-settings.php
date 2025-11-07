<?php

if (!defined('ABSPATH')) exit;

class LRob_Carte_Settings {

    public static function get($key, $default = '') {
        return get_option('lrob_carte_' . $key, $default);
    }

    public static function update($key, $value) {
        return update_option('lrob_carte_' . $key, $value);
    }

    public static function get_allergens() {
        return array(
            'gluten' => __('Gluten', 'lrob-la-carte'),
                     'crustaces' => __('Crustacés', 'lrob-la-carte'),
                     'oeufs' => __('Œufs', 'lrob-la-carte'),
                     'poisson' => __('Poisson', 'lrob-la-carte'),
                     'arachides' => __('Arachides', 'lrob-la-carte'),
                     'soja' => __('Soja', 'lrob-la-carte'),
                     'lait' => __('Lait', 'lrob-la-carte'),
                     'fruits_coque' => __('Fruits à coque', 'lrob-la-carte'),
                     'celeri' => __('Céleri', 'lrob-la-carte'),
                     'moutarde' => __('Moutarde', 'lrob-la-carte'),
                     'sesame' => __('Sésame', 'lrob-la-carte'),
                     'sulfites' => __('Sulfites', 'lrob-la-carte'),
                     'lupin' => __('Lupin', 'lrob-la-carte'),
                     'mollusques' => __('Mollusques', 'lrob-la-carte'),
        );
    }

    public static function get_badges() {
        return array(
            'nouveau' => __('Nouveau', 'lrob-la-carte'),
                     'coup_coeur' => __('Coup de cœur', 'lrob-la-carte'),
                     'vegetarien' => __('Végétarien', 'lrob-la-carte'),
                     'vegan' => __('Vegan', 'lrob-la-carte'),
                     'bio' => __('Bio', 'lrob-la-carte'),
                     'fait_maison' => __('Fait maison', 'lrob-la-carte'),
                     'sans_gluten' => __('Sans gluten', 'lrob-la-carte'),
                     'epicé' => __('Épicé', 'lrob-la-carte'),
        );
    }

    public static function get_emoji_presets() {
        return array(
            '🍽️', '🍕', '🍔', '🍟', '🌭', '🥪', '🌮', '🌯', '🥗', '🍝',
            '🍜', '🍲', '🍱', '🍣', '🍤', '🍙', '🍚', '🍛', '🍢', '🥘',
            '🍰', '🎂', '🧁', '🥧', '🍮', '🍭', '🍬', '🍫', '🍿', '🍩',
            '🍪', '🍺', '🍻', '🍷', '🍸', '🍹', '🍾', '🥂', '🥃', '🥤',
            '☕', '🍵', '🧃', '🧉', '🧊', '🥛', '🍼', '🥄', '🍴', '🥢'
        );
    }
}
