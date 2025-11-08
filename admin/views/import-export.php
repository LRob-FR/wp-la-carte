<div class="wrap lrob-carte-admin">
    <h1><?php _e('Import / Export', 'lrob-la-carte'); ?></h1>

    <div class="lrob-import-export-grid">

        <!-- Export Section -->
        <div class="lrob-box">
            <h2><?php _e('Export', 'lrob-la-carte'); ?></h2>
            <p><?php _e('Export all your categories, products and settings as a JSON file.', 'lrob-la-carte'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('lrob_export', 'lrob_export_nonce'); ?>
                <p>
                    <button type="submit" class="button button-primary">
                        <?php _e('Export Data', 'lrob-la-carte'); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Import Section -->
        <div class="lrob-box">
            <h2><?php _e('Import', 'lrob-la-carte'); ?></h2>
            <p><?php _e('Import categories and products from a JSON file.', 'lrob-la-carte'); ?></p>

            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('lrob_import', 'lrob_import_nonce'); ?>

                <p>
                    <input type="file" name="import_file" accept=".json" required>
                </p>

                <p>
                    <button type="submit" class="button button-primary">
                        <?php _e('Import Data', 'lrob-la-carte'); ?>
                    </button>
                </p>
            </form>

            <div class="lrob-import-notice">
                <strong><?php _e('Important:', 'lrob-la-carte'); ?></strong>
                <ul>
                    <li><?php _e('Existing categories with the same slug will be updated.', 'lrob-la-carte'); ?></li>
                    <li><?php _e('Products will be added (not updated).', 'lrob-la-carte'); ?></li>
                    <li><?php _e('Images will be downloaded and imported into your media library.', 'lrob-la-carte'); ?></li>
                    <li><?php _e('Settings will be overwritten.', 'lrob-la-carte'); ?></li>
                </ul>
            </div>
        </div>

    </div>
</div>
