jQuery(document).ready(function ($) {

    // ---- Helpers ----
    function toId(val) { return String(val || '').replace(/[^0-9]/g, ''); }
    function toInt(val, def = 0) { const n = parseInt(val, 10); return isNaN(n) ? def : n; }
    function isObj(x) { return x && typeof x === 'object'; }
    function hasAjaxCfg() { return typeof window.lrobCarte === 'object' && lrobCarte.ajaxurl && lrobCarte.nonce; }
    function safeImg($container, url, opts) {
        $container.empty();
        if (!url || typeof url !== 'string') return;
        // Create <img> via DOM API (prevents attribute injection)
        const img = document.createElement('img');
        img.setAttribute('src', url);
        if (opts && opts.style) img.setAttribute('style', opts.style);
        $container.append(img);
    }

    // Early guard: if missing ajax config, we keep UI actions but avoid broken calls.
    const ajaxDisabled = !hasAjaxCfg();

    // ---- WP color pickers ----
    $('.lrob-color-picker').wpColorPicker && $('.lrob-color-picker').wpColorPicker();

    // ---- Modal management ----
    function openModal(modalId) { $('#' + modalId).fadeIn(200); }
    function closeModal(modalId) { $('#' + modalId).fadeOut(200); }

    $('.lrob-modal-close').on('click', function () {
        $(this).closest('.lrob-modal').fadeOut(200);
    });

    $(window).on('click', function (e) {
        if ($(e.target).hasClass('lrob-modal')) {
            $(e.target).fadeOut(200);
        }
    });

    // ========== PRODUCTS ==========

    $('#lrob-add-product').on('click', function () {
        resetProductForm();
        $('#lrob-modal-title').text('Add Product');
        openModal('lrob-product-modal');
    });

    // Synchronize category select with hidden field
    $(document).on('change', '#product-category-select', function () {
        $('#product-category').val(toId($(this).val()));
    });

    $('.lrob-edit-product').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        const productId = toId($(this).data('id'));

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_get_product',
                nonce: lrobCarte.nonce,
                id: productId
            }
        }).done(function (response) {
            if (response && response.success && isObj(response.data) && isObj(response.data.product)) {
                const prices = Array.isArray(response.data.prices) ? response.data.prices : [];
                populateProductForm(response.data.product, prices);
                $('#lrob-modal-title').text('Edit Product');
                openModal('lrob-product-modal');
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Unable to load product.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    $('.lrob-delete-product').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        if (!confirm('Are you sure you want to delete this product?')) return;

        const productId = toId($(this).data('id'));

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_delete_product',
                nonce: lrobCarte.nonce,
                id: productId
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Deletion failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    // Up/Down buttons for products
    $('.lrob-move-product-up').on('click', function () {
        const $item = $(this).closest('.lrob-product-item');
        const $prev = $item.prev('.lrob-product-item');
        if ($prev.length) {
            $item.insertBefore($prev);
            updateProductPositions();
        }
    });

    $('.lrob-move-product-down').on('click', function () {
        const $item = $(this).closest('.lrob-product-item');
        const $next = $item.next('.lrob-product-item');
        if ($next.length) {
            $item.insertAfter($next);
            updateProductPositions();
        }
    });

    function updateProductPositions() {
        if (ajaxDisabled) return;
        const positions = [];
        $('#lrob-products-list .lrob-product-item').each(function () {
            positions.push(toId($(this).data('id')));
        });

        // Cap payload to avoid DoS via massive DOM (safety belt; adjust if needed)
        const capped = positions.slice(0, 1000);

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_positions',
                nonce: lrobCarte.nonce,
                table: 'products',
                positions: capped
            }
        }).fail(function () {
            alert('Failed to update positions.');
        });
    }

    $('#lrob-product-form').on('submit', function (e) {
        e.preventDefault();
        if (ajaxDisabled) return alert('AJAX config missing.');

        const allergens = [];
        $('.allergen-checkbox:checked').each(function () {
            allergens.push(String($(this).val() || '').trim());
        });

        const badges = [];
        $('.badge-checkbox:checked').each(function () {
            badges.push(String($(this).val() || '').trim());
        });

        const prices = [];
        $('#product-prices-wrapper .lrob-price-row').each(function () {
            const label = $(this).find('.price-label').val();
            const amount = $(this).find('.price-amount').val();
            const happyHour = $(this).find('.price-happy-hour').is(':checked') ? 1 : 0;
            if (amount) {
                prices.push({
                    label: String(label || ''),
                    price: String(amount),
                    happy_hour: happyHour
                });
            }
        });

        // Cap number of prices to a reasonable limit
        const pricesCapped = prices.slice(0, 100);

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_save_product',
                nonce: lrobCarte.nonce,
                id: toId($('#product-id').val()),
                category_id: toId($('#product-category').val()),
                name: $('#product-name').val(),
                description: $('#product-description').val(),
                image_id: toId($('#product-image-id').val()),
                allergens: allergens.join(','),
                badges: badges.join(','),
                availability: $('#product-availability').val(),
                position: toId($('#product-position').val()),
                prices: pricesCapped
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Save failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    // Media uploader (Product)
    let mediaUploader;

    $('#product-upload-image').on('click', function (e) {
        e.preventDefault();

        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('WordPress media library is not loaded. Please refresh the page.');
            return;
        }

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Choose Image',
            button: { text: 'Use This Image' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#product-image-id').val(toId(attachment.id));
            // Safe DOM injection (no string HTML)
            safeImg($('#product-image-preview'), attachment.url);
            $('#product-remove-image').show();
        });

        mediaUploader.open();
    });

    $('#product-remove-image').on('click', function () {
        $('#product-image-id').val('0');
        $('#product-image-preview').empty();
        $(this).hide();
    });

    // Price management with suggestions
    const usedPriceLabels = new Set();

    function collectExistingPriceLabels() {
        usedPriceLabels.clear();
        $('.lrob-price-label').each(function () {
            const label = $(this).text().trim();
            if (label) usedPriceLabels.add(label);
        });
    }
    collectExistingPriceLabels();

    function createPriceRow(isHappyHour = false) {
        const mode = $('#product-mode-hidden').val() || 'restaurant';
        let happyHourCheckbox = '';

        if (mode === 'bar') {
            happyHourCheckbox =
                '<label class="lrob-happy-hour-label">' +
                '<input type="checkbox" class="price-happy-hour"' + (isHappyHour ? ' checked' : '') + '> Happy Hour' +
                '</label>';
        }

        // Static HTML (no untrusted data included here)
        const newRow =
            '<div class="lrob-price-row' + (isHappyHour ? ' lrob-happy-hour-row' : '') + '">' +
            '<input type="text" class="price-label" placeholder="Ex: Verre (12cl)" list="price-labels-suggestions">' +
            '<input type="number" class="price-amount" placeholder="0.00" step="0.01" min="0">' +
            happyHourCheckbox +
            '<button type="button" class="button lrob-remove-price">−</button>' +
            '</div>';

        return newRow;
    }

    $('#lrob-add-price').on('click', function () {
        $('#product-prices-wrapper').append(createPriceRow());
    });

    $(document).on('click', '.lrob-remove-price', function () {
        if ($('#product-prices-wrapper .lrob-price-row').length > 1) {
            $(this).closest('.lrob-price-row').remove();
        }
    });

    $(document).on('change', '.price-happy-hour', function () {
        const $row = $(this).closest('.lrob-price-row');
        $row.toggleClass('lrob-happy-hour-row', $(this).is(':checked'));
    });

    function resetProductForm() {
        $('#product-id').val('');
        $('#product-name').val('');
        $('#product-description').val('');
        $('#product-image-id').val('0');
        $('#product-image-preview').empty();
        $('#product-remove-image').hide();
        $('#product-availability').val('available');
        $('.allergen-checkbox').prop('checked', false);
        $('.badge-checkbox').prop('checked', false);
        $('#product-prices-wrapper').html(createPriceRow());
    }

    function populateProductForm(product, prices) {
        if (!isObj(product)) return;
        $('#product-id').val(toId(product.id));
        $('#product-category').val(toId(product.category_id));
        $('#product-category-select').val(toId(product.category_id));
        $('#product-name').val(product.name || '');
        $('#product-description').val(product.description || '');
        $('#product-availability').val(product.availability || 'available');
        $('#product-position').val(toInt(product.position, 0));

        // Image preview safely
        if (product.image_url) {
            $('#product-image-id').val(toId(product.image_id || 0));
            safeImg($('#product-image-preview'), String(product.image_url), { style: 'max-width: 200px; border-radius: 4px;' });
            $('#product-remove-image').show();
        } else {
            $('#product-image-id').val('0');
            $('#product-image-preview').empty();
            $('#product-remove-image').hide();
        }

        const allergensList = (product.allergens ? String(product.allergens).split(',') : []).map(s => s.trim());
        $('.allergen-checkbox').prop('checked', false);
        allergensList.forEach(function (allergen) {
            $('.allergen-checkbox[value="' + allergen + '"]').prop('checked', true);
        });

        const badgesList = (product.badges ? String(product.badges).split(',') : []).map(s => s.trim());
        $('.badge-checkbox').prop('checked', false);
        badgesList.forEach(function (badge) {
            $('.badge-checkbox[value="' + badge + '"]').prop('checked', true);
        });

        $('#product-prices-wrapper').html('');
        if (Array.isArray(prices) && prices.length > 0) {
            prices.slice(0, 100).forEach(function (price) {
                const isHappyHour = String(price.happy_hour) === '1';
                const $row = $(createPriceRow(isHappyHour));
                $row.find('.price-label').val(price.label || '');
                $row.find('.price-amount').val(price.price);
                if (isHappyHour) $row.find('.price-happy-hour').prop('checked', true);
                $('#product-prices-wrapper').append($row);
            });
        } else {
            $('#product-prices-wrapper').html(createPriceRow());
        }
    }

    // ========== ADMIN CATEGORY FILTERING (like frontend) ==========

    const filterWrapper = $('[data-admin-filter-wrapper]');
    if (filterWrapper.length > 0) {
        const rootTabs = filterWrapper.find('.lrob-admin-root-tab');
        const allProducts = $('#lrob-products-grid .lrob-product-card');

        // Initialize: show first root category on load
        if (rootTabs.length > 0) {
            const firstRootId = toId(rootTabs.first().data('root-category'));
            rootTabs.first().addClass('active');
            $('.lrob-level-1-filters[data-parent-id="' + firstRootId + '"]').show();
            filterProductsByRootCategory(firstRootId);
        }

        // Root category tab click
        rootTabs.on('click', function () {
            const rootCategoryId = toId($(this).data('root-category'));
            rootTabs.removeClass('active');
            $(this).addClass('active');

            $('.lrob-level-1-filters, .lrob-level-2-filters').hide();
            $('.lrob-subcategory-badge').removeClass('active');
            $('.lrob-level-1-filters[data-parent-id="' + rootCategoryId + '"]').show();

            filterProductsByRootCategory(rootCategoryId);
        });

        // Level 1 subcategory badge click
        $(document).on('click', '.lrob-subcategory-badge[data-filter-level="1"]', function () {
            const subcategoryId = toId($(this).data('subcategory-id'));
            const parentId = toId($(this).data('parent-id'));
            const isActive = $(this).hasClass('active');

            if (isActive) {
                $(this).removeClass('active');
                $('.lrob-level-2-filters').hide();
                $('.lrob-subcategory-badge[data-filter-level="2"]').removeClass('active');
                filterProductsByRootCategory(parentId);
            } else {
                $('.lrob-subcategory-badge[data-filter-level="1"][data-parent-id="' + parentId + '"]').removeClass('active');
                $(this).addClass('active');
                $('.lrob-level-2-filters').hide();
                $('.lrob-subcategory-badge[data-filter-level="2"]').removeClass('active');
                $('.lrob-level-2-filters[data-parent-id="' + subcategoryId + '"]').show();
                filterProductsByCategory(subcategoryId, null);
            }
        });

        // Level 2 subcategory badge click
        $(document).on('click', '.lrob-subcategory-badge[data-filter-level="2"]', function () {
            const subcategoryId = toId($(this).data('subcategory-id'));
            const parentId = toId($(this).data('parent-id'));
            const isActive = $(this).hasClass('active');

            if (isActive) {
                $(this).removeClass('active');
                filterProductsByCategory(parentId, null);
            } else {
                $('.lrob-subcategory-badge[data-filter-level="2"][data-parent-id="' + parentId + '"]').removeClass('active');
                $(this).addClass('active');
                filterProductsByCategory(parentId, subcategoryId);
            }
        });

        function filterProductsByRootCategory(rootCategoryId) {
            allProducts.each(function () {
                const $product = $(this);
                const ancestors = String($product.data('category-ancestors') || '').split(',');
                $product.toggle(ancestors.indexOf(String(rootCategoryId)) !== -1);
            });
        }

        function filterProductsByCategory(level1CategoryId, level2CategoryId) {
            allProducts.each(function () {
                const $product = $(this);
                const ancestors = String($product.data('category-ancestors') || '').split(',');

                if (level2CategoryId) {
                    $product.toggle(ancestors.indexOf(String(level2CategoryId)) !== -1);
                } else if (level1CategoryId) {
                    $product.toggle(ancestors.indexOf(String(level1CategoryId)) !== -1);
                }
            });
        }
    }

    // ========== CATEGORIES ==========

    $('#lrob-add-category').on('click', function () {
        resetCategoryForm();
        $('#lrob-modal-title').text('Add Category');
        openModal('lrob-category-modal');
    });

    $('.lrob-edit-category').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        const categoryId = toId($(this).data('id'));

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_get_category',
                nonce: lrobCarte.nonce,
                id: categoryId
            }
        }).done(function (response) {
            if (response && response.success && isObj(response.data)) {
                populateCategoryForm(response.data);
                $('#lrob-modal-title').text('Edit Category');
                openModal('lrob-category-modal');
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Unable to load category.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    $('.lrob-delete-category').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        if (!confirm('Are you sure you want to delete this category?')) return;

        const categoryId = toId($(this).data('id'));

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_delete_category',
                nonce: lrobCarte.nonce,
                id: categoryId
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Deletion failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    $('.lrob-toggle-category').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        const categoryId = toId($(this).data('id'));

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_toggle_category',
                nonce: lrobCarte.nonce,
                id: categoryId
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Update failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    // Up/Down buttons for categories
    $('.lrob-move-category-up').on('click', function () {
        const $item = $(this).closest('.lrob-category-item');
        const $prev = $item.prev('.lrob-category-item');
        if ($prev.length) {
            $item.insertBefore($prev);
            updateCategoryPositions();
        }
    });

    $('.lrob-move-category-down').on('click', function () {
        const $item = $(this).closest('.lrob-category-item');
        const $next = $item.next('.lrob-category-item');
        if ($next.length) {
            $item.insertAfter($next);
            updateCategoryPositions();
        }
    });

    function updateCategoryPositions() {
        if (ajaxDisabled) return;
        const positions = [];
        $('#lrob-categories-list .lrob-category-item').each(function () {
            positions.push(toId($(this).data('id')));
        });

        const capped = positions.slice(0, 1000);

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_positions',
                nonce: lrobCarte.nonce,
                table: 'categories',
                positions: capped
            }
        }).fail(function () {
            alert('Failed to update positions.');
        });
    }

    // Indent / Unindent
    $('.lrob-indent-category').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        const $item = $(this).closest('.lrob-category-item');
        const catId = toId($item.data('id'));
        const $prev = $item.prev('.lrob-category-item');

        if (!$prev.length) {
            alert('Cannot create a subcategory here');
            return;
        }

        const newParentId = toId($prev.data('id'));

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_category_parent',
                nonce: lrobCarte.nonce,
                id: catId,
                parent_id: newParentId
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Update failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    $('.lrob-unindent-category').on('click', function () {
        if (ajaxDisabled) return alert('AJAX config missing.');
        const $item = $(this).closest('.lrob-category-item');
        const catId = toId($item.data('id'));
        const currentParent = toId($item.data('parent'));

        const $parentItem = $('.lrob-category-item[data-id="' + currentParent + '"]');
        const newParentId = $parentItem.length ? toId($parentItem.data('parent') || 0) : '0';

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_category_parent',
                nonce: lrobCarte.nonce,
                id: catId,
                parent_id: newParentId
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Update failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    $('#lrob-category-form').on('submit', function (e) {
        e.preventDefault();
        if (ajaxDisabled) return alert('AJAX config missing.');

        const iconType = $('input[name="icon-type"]:checked').val();
        let iconValue = '';

        if (iconType === 'emoji') {
            iconValue = $('#category-emoji').val();
        } else if (iconType === 'image') {
            iconValue = toId($('#category-icon-image-id').val());
        }

        if (!$('#category-slug').val()) {
            $('#category-slug').val($('#category-name').val().toLowerCase().replace(/[^a-z0-9]+/g, '-'));
        }

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_save_category',
                nonce: lrobCarte.nonce,
                id: toId($('#category-id').val()),
                parent_id: toId($('#category-parent').val()),
                name: $('#category-name').val(),
                slug: $('#category-slug').val(),
                icon_type: iconType,
                icon_value: iconValue,
                position: toId($('#category-position').val()),
                active: $('#category-active').is(':checked') ? 1 : 0
            }
        }).done(function (response) {
            if (response && response.success) {
                location.reload();
            } else if (response && response.data && response.data.message) {
                alert(response.data.message);
            } else {
                alert('Save failed.');
            }
        }).fail(function () {
            alert('Request failed.');
        });
    });

    $('input[name="icon-type"]').on('change', function () {
        const iconType = $(this).val();
        $('#icon-emoji-row, #icon-image-row').hide();

        if (iconType === 'emoji') {
            $('#icon-emoji-row').show();
        } else if (iconType === 'image') {
            $('#icon-image-row').show();
        }
    });

    $('.lrob-emoji-option').on('click', function () {
        $('#category-emoji').val($(this).data('emoji'));
    });

    // Media uploader for custom icon (Category)
    let categoryIconUploader;

    $('#category-upload-icon-image').on('click', function (e) {
        e.preventDefault();

        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('WordPress media library is not loaded. Please refresh the page.');
            return;
        }

        if (categoryIconUploader) {
            categoryIconUploader.open();
            return;
        }

        categoryIconUploader = wp.media({
            title: 'Choose Icon',
            button: { text: 'Use This Image' },
            multiple: false,
            library: { type: 'image' }
        });

        categoryIconUploader.on('select', function () {
            const attachment = categoryIconUploader.state().get('selection').first().toJSON();
            $('#category-icon-image-id').val(toId(attachment.id));
            safeImg($('#category-icon-image-preview'), attachment.url, { style: 'max-width: 50px; max-height: 50px;' });
            $('#category-remove-icon-image').show();
        });

        categoryIconUploader.open();
    });

    $('#category-remove-icon-image').on('click', function () {
        $('#category-icon-image-id').val('');
        $('#category-icon-image-preview').empty();
        $(this).hide();
    });

    function resetCategoryForm() {
        $('#category-id').val('');
        $('#category-parent').val('0');
        $('#category-name').val('');
        $('#category-slug').val('');
        $('#category-emoji').val('🍽️');
        $('#category-icon-image-id').val('');
        $('#category-icon-image-preview').empty();
        $('#category-remove-icon-image').hide();
        $('#category-active').prop('checked', true);
        $('input[name="icon-type"][value="emoji"]').prop('checked', true).trigger('change');
    }

    function populateCategoryForm(category) {
        if (!isObj(category)) return;
        $('#category-id').val(toId(category.id));
        $('#category-parent').val(toId(category.parent_id || '0'));
        $('#category-name').val(category.name || '');
        $('#category-slug').val(category.slug || '');
        $('#category-position').val(toInt(category.position, 0));
        $('#category-active').prop('checked', String(category.active) === '1');

        if (category.icon_type === 'emoji') {
            $('input[name="icon-type"][value="emoji"]').prop('checked', true);
            $('#category-emoji').val(category.icon_value || '🍽️');
            $('#category-icon-image-id').val('');
            $('#category-icon-image-preview').empty();
            $('#category-remove-icon-image').hide();
        } else if (category.icon_type === 'image') {
            $('input[name="icon-type"][value="image"]').prop('checked', true);
            const id = toId(category.icon_value);
            $('#category-icon-image-id').val(id);

            if (id) {
                // This action name looks non-standard; leaving as-is to avoid backend changes.
                $.ajax({
                    url: lrobCarte.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_ajax_get_attachment_url',
                        attachment_id: id
                    }
                }).done(function (url) {
                    if (url && typeof url === 'string') {
                        safeImg($('#category-icon-image-preview'), url, { style: 'max-width: 50px; max-height: 50px;' });
                        $('#category-remove-icon-image').show();
                    }
                });
            } else {
                $('#category-icon-image-preview').empty();
                $('#category-remove-icon-image').hide();
            }
        }

        $('input[name="icon-type"]:checked').trigger('change');
    }
});
