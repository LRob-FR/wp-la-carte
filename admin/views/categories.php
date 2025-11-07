<div class="wrap lrob-carte-admin">
    <h1>
        <?php _e('Catégories', 'lrob-la-carte'); ?>
        <button class="button button-primary" id="lrob-add-category"><?php _e('Ajouter une catégorie', 'lrob-la-carte'); ?></button>
    </h1>

    <div id="lrob-categories-list" class="lrob-sortable">
        <?php 
        // Organiser les catégories par hiérarchie
        $categories_by_parent = array();
        foreach ($categories as $cat) {
            $parent_id = $cat->parent_id ?? 0;
            if (!isset($categories_by_parent[$parent_id])) {
                $categories_by_parent[$parent_id] = array();
            }
            $categories_by_parent[$parent_id][] = $cat;
        }
        
        // Fonction récursive pour afficher la hiérarchie
        function display_category_tree($parent_id, $categories_by_parent, $level = 0) {
            if (!isset($categories_by_parent[$parent_id])) return;
            
            foreach ($categories_by_parent[$parent_id] as $cat):
                $indent_class = $level > 0 ? 'lrob-category-child level-' . $level : '';
        ?>
            <div class="lrob-category-item <?php echo $indent_class; ?> <?php echo $cat->active ? '' : 'lrob-inactive'; ?>" 
                 data-id="<?php echo $cat->id; ?>" 
                 data-parent="<?php echo $cat->parent_id ?? 0; ?>"
                 data-level="<?php echo $level; ?>">
                <div class="lrob-category-order-buttons">
                    <button class="button button-small lrob-move-category-up" title="<?php _e('Monter', 'lrob-la-carte'); ?>">↑</button>
                    <button class="button button-small lrob-move-category-down" title="<?php _e('Descendre', 'lrob-la-carte'); ?>">↓</button>
                </div>
                
                <?php if ($level > 0): ?>
                    <div class="lrob-category-indent">
                        <?php echo str_repeat('└─ ', $level); ?>
                    </div>
                <?php endif; ?>
                
                <div class="lrob-category-icon">
                    <?php 
                    if ($cat->icon_type === 'emoji') {
                        echo esc_html($cat->icon_value);
                    } elseif ($cat->icon_type === 'image' && $cat->icon_value) {
                        echo wp_get_attachment_image($cat->icon_value, array(24, 24));
                    } else {
                        echo '<i class="' . esc_attr($cat->icon_value) . '"></i>';
                    }
                    ?>
                </div>

                <div class="lrob-category-info">
                    <div class="lrob-category-name">
                        <?php echo esc_html($cat->name); ?>
                        <?php if (!$cat->active): ?>
                            <span class="lrob-badge lrob-badge-inactive"><?php _e('Désactivée', 'lrob-la-carte'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lrob-category-slug"><?php echo esc_html($cat->slug); ?></div>
                </div>

                <div class="lrob-category-actions">
                    <?php if ($level > 0): ?>
                        <button class="button button-small lrob-unindent-category" data-id="<?php echo $cat->id; ?>" title="<?php _e('Remonter d\'un niveau', 'lrob-la-carte'); ?>">
                            ← <?php _e('Niveau sup.', 'lrob-la-carte'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($level < 2): // Max 3 niveaux ?>
                        <button class="button button-small lrob-indent-category" data-id="<?php echo $cat->id; ?>" title="<?php _e('Descendre d\'un niveau', 'lrob-la-carte'); ?>">
                            → <?php _e('Sous-catégorie', 'lrob-la-carte'); ?>
                        </button>
                    <?php endif; ?>
                    <button class="button lrob-toggle-category" data-id="<?php echo $cat->id; ?>" data-active="<?php echo $cat->active; ?>">
                        <?php echo $cat->active ? __('Désactiver', 'lrob-la-carte') : __('Activer', 'lrob-la-carte'); ?>
                    </button>
                    <button class="button lrob-edit-category" data-id="<?php echo $cat->id; ?>">
                        <?php _e('Modifier', 'lrob-la-carte'); ?>
                    </button>
                    <button class="button lrob-delete-category" data-id="<?php echo $cat->id; ?>">
                        <?php _e('Supprimer', 'lrob-la-carte'); ?>
                    </button>
                </div>
            </div>
            <?php
                // Afficher récursivement les enfants
                display_category_tree($cat->id, $categories_by_parent, $level + 1);
            endforeach;
        }
        
        // Afficher à partir des catégories racines (parent_id = 0)
        display_category_tree(0, $categories_by_parent);
        ?>
    </div>
</div>

<div id="lrob-category-modal" class="lrob-modal" style="display:none;">
    <div class="lrob-modal-content lrob-modal-small">
        <span class="lrob-modal-close">&times;</span>
        <h2 id="lrob-category-modal-title"><?php _e('Ajouter une catégorie', 'lrob-la-carte'); ?></h2>
        
        <form id="lrob-category-form">
            <input type="hidden" id="category-id" value="">
            <input type="hidden" id="category-position" value="999">

            <table class="form-table">
                <tr>
                    <th><label for="category-name"><?php _e('Nom', 'lrob-la-carte'); ?> *</label></th>
                    <td><input type="text" id="category-name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="category-slug"><?php _e('Slug', 'lrob-la-carte'); ?></label></th>
                    <td><input type="text" id="category-slug" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="category-parent"><?php _e('Catégorie parente', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <select id="category-parent" class="regular-text">
                            <option value="0"><?php _e('Aucune (catégorie principale)', 'lrob-la-carte'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Permet de créer une hiérarchie de catégories (ex: Cocktails sous Boissons)', 'lrob-la-carte'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="category-active"><?php _e('Statut', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="category-active" value="1" checked>
                            <?php _e('Catégorie active (visible sur le site)', 'lrob-la-carte'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Type d\'icône', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <label>
                            <input type="radio" name="icon-type" value="emoji" checked> <?php _e('Emoji', 'lrob-la-carte'); ?>
                        </label>
                        <label>
                            <input type="radio" name="icon-type" value="image"> <?php _e('Image personnalisée', 'lrob-la-carte'); ?>
                        </label>
                    </td>
                </tr>
                <tr id="icon-emoji-row">
                    <th><label><?php _e('Emoji', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <div class="lrob-emoji-picker">
                            <?php foreach (LRob_Carte_Settings::get_emoji_presets() as $emoji): ?>
                                <span class="lrob-emoji-option" data-emoji="<?php echo $emoji; ?>"><?php echo $emoji; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" id="category-emoji" value="🍽️" maxlength="2">
                    </td>
                </tr>
                <tr id="icon-image-row" style="display:none;">
                    <th><label><?php _e('Image personnalisée', 'lrob-la-carte'); ?></label></th>
                    <td>
                        <input type="hidden" id="category-icon-image-id" value="">
                        <div id="category-icon-image-preview" style="margin-bottom: 10px;"></div>
                        <button type="button" class="button" id="category-upload-icon-image"><?php _e('Choisir une image', 'lrob-la-carte'); ?></button>
                        <button type="button" class="button" id="category-remove-icon-image" style="display:none;"><?php _e('Retirer', 'lrob-la-carte'); ?></button>
                        <p class="description"><?php _e('Format recommandé : 64x64px, PNG avec fond transparent', 'lrob-la-carte'); ?></p>
                    </td>
                </tr>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Enregistrer', 'lrob-la-carte'); ?></button>
                <button type="button" class="button lrob-modal-close"><?php _e('Annuler', 'lrob-la-carte'); ?></button>
            </p>
        </form>
    </div>
</div>
