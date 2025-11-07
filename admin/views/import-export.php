<div class="wrap lrob-carte-admin">
    <h1><?php _e('Import / Export', 'lrob-la-carte'); ?></h1>

    <div class="lrob-import-export-grid">
        <div class="lrob-box">
            <h2><?php _e('Exporter', 'lrob-la-carte'); ?></h2>
            <p><?php _e('Téléchargez toutes vos catégories et produits au format JSON.', 'lrob-la-carte'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('lrob_export', 'lrob_export_nonce'); ?>
                <button type="submit" class="button button-primary">
                    <?php _e('Exporter au format JSON', 'lrob-la-carte'); ?>
                </button>
            </form>
        </div>

        <div class="lrob-box">
            <h2><?php _e('Import', 'lrob-la-carte'); ?></h2>
            <p><?php _e('Importez un fichier JSON précédemment exporté. Les catégories et produits existants seront conservés.', 'lrob-la-carte'); ?></p>

            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('lrob_import', 'lrob_import_nonce'); ?>

                <p>
                    <input type="file" name="import_file" accept=".json" required>
                </p>

                <button type="submit" class="button button-primary">
                    <?php _e('Import', 'lrob-la-carte'); ?>
                </button>
            </form>

            <div class="lrob-import-notice">
                <p><strong><?php _e('Note:', 'lrob-la-carte'); ?></strong></p>
                <ul>
                    <li><?php _e('Les catégories avec le même slug seront mises à jour.', 'lrob-la-carte'); ?></li>
                    <li><?php _e('Les produits seront ajoutés aux catégories correspondantes.', 'lrob-la-carte'); ?></li>
                    <li><?php _e('Les images seront téléchargées depuis leurs URLs.', 'lrob-la-carte'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
