jQuery(document).ready(function($) {

    $('.lrob-color-picker').wpColorPicker();

    // Modal management
    function openModal(modalId) {
        $('#' + modalId).fadeIn(200);
    }

    function closeModal(modalId) {
        $('#' + modalId).fadeOut(200);
    }

    $('.lrob-modal-close').on('click', function() {
        $(this).closest('.lrob-modal').fadeOut(200);
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('lrob-modal')) {
            $(e.target).fadeOut(200);
        }
    });

    // ========== PRODUCTS ==========

    $('#lrob-add-product').on('click', function() {
        resetProductForm();
        $('#lrob-modal-title').text('Add Product');
        openModal('lrob-product-modal');
    });

    // Synchronize category select with hidden field
    $(document).on('change', '#product-category-select', function() {
        $('#product-category').val($(this).val());
    });

    $('.lrob-edit-product').on('click', function() {
        var productId = $(this).data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_get_product',
                nonce: lrobCarte.nonce,
                id: productId
            },
            success: function(response) {
                if (response.success) {
                    populateProductForm(response.data.product, response.data.prices);
                    $('#lrob-modal-title').text('Edit Product');
                    openModal('lrob-product-modal');
                }
            }
        });
    });

    $('.lrob-delete-product').on('click', function() {
        if (!confirm('Are you sure you want to delete this product?')) return;

        var productId = $(this).data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_delete_product',
                nonce: lrobCarte.nonce,
                id: productId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Up/Down buttons for products
    $('.lrob-move-product-up').on('click', function() {
        var $item = $(this).closest('.lrob-product-item');
        var $prev = $item.prev('.lrob-product-item');
        if ($prev.length) {
            $item.insertBefore($prev);
            updateProductPositions();
        }
    });

    $('.lrob-move-product-down').on('click', function() {
        var $item = $(this).closest('.lrob-product-item');
        var $next = $item.next('.lrob-product-item');
        if ($next.length) {
            $item.insertAfter($next);
            updateProductPositions();
        }
    });

    function updateProductPositions() {
        var positions = [];
        $('#lrob-products-list .lrob-product-item').each(function(index) {
            positions.push($(this).data('id'));
        });

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_positions',
                nonce: lrobCarte.nonce,
                table: 'products',
                positions: positions
            }
        });
    }

    $('#lrob-product-form').on('submit', function(e) {
        e.preventDefault();

        var allergens = [];
        $('.allergen-checkbox:checked').each(function() {
            allergens.push($(this).val());
        });

        var badges = [];
        $('.badge-checkbox:checked').each(function() {
            badges.push($(this).val());
        });

        var prices = [];
        $('#product-prices-wrapper .lrob-price-row').each(function() {
            var label = $(this).find('.price-label').val();
            var amount = $(this).find('.price-amount').val();
            var happyHour = $(this).find('.price-happy-hour').is(':checked') ? 1 : 0;
            if (amount) {
                prices.push({
                    label: label,
                    price: amount,
                    happy_hour: happyHour
                });
            }
        });

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_save_product',
                nonce: lrobCarte.nonce,
                id: $('#product-id').val(),
                category_id: $('#product-category').val(),
                name: $('#product-name').val(),
                description: $('#product-description').val(),
                image_id: $('#product-image-id').val(),
                allergens: allergens.join(','),
                badges: badges.join(','),
                availability: $('#product-availability').val(),
                position: $('#product-position').val(),
                prices: prices
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Media uploader
    var mediaUploader;

    $('#product-upload-image').on('click', function(e) {
        e.preventDefault();

        // Check if wp.media is available
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

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#product-image-id').val(attachment.id);
            $('#product-image-preview').html('<img src="' + attachment.url + '">');
            $('#product-remove-image').show();
        });

        mediaUploader.open();
    });

    $('#product-remove-image').on('click', function() {
        $('#product-image-id').val('0');
        $('#product-image-preview').html('');
        $(this).hide();
    });

    // Price management with suggestions
    var usedPriceLabels = new Set();

    // Collect existing labels on page load
    function collectExistingPriceLabels() {
        usedPriceLabels.clear();
        $('.lrob-price-label').each(function() {
            var label = $(this).text().trim();
            if (label) {
                usedPriceLabels.add(label);
            }
        });
    }

    collectExistingPriceLabels();

    function createPriceRow(isHappyHour = false) {
        var mode = $('#product-mode-hidden').val() || 'restaurant';
        var happyHourCheckbox = '';

        if (mode === 'bar') {
            happyHourCheckbox = '<label class="lrob-happy-hour-label">' +
                '<input type="checkbox" class="price-happy-hour"' + (isHappyHour ? ' checked' : '') + '> Happy Hour' +
                '</label>';
        }

        var newRow = '<div class="lrob-price-row' + (isHappyHour ? ' lrob-happy-hour-row' : '') + '">' +
            '<input type="text" class="price-label" placeholder="Ex: Verre (12cl)" list="price-labels-suggestions">' +
            '<input type="number" class="price-amount" placeholder="0.00" step="0.01" min="0">' +
            happyHourCheckbox +
            '<button type="button" class="button lrob-remove-price">−</button>' +
            '</div>';

        return newRow;
    }

    $('#lrob-add-price').on('click', function() {
        $('#product-prices-wrapper').append(createPriceRow());
    });

    $(document).on('click', '.lrob-remove-price', function() {
        if ($('#product-prices-wrapper .lrob-price-row').length > 1) {
            $(this).closest('.lrob-price-row').remove();
        }
    });

    $(document).on('change', '.price-happy-hour', function() {
        var $row = $(this).closest('.lrob-price-row');
        if ($(this).is(':checked')) {
            $row.addClass('lrob-happy-hour-row');
        } else {
            $row.removeClass('lrob-happy-hour-row');
        }
    });

    function resetProductForm() {
        $('#product-id').val('');
        $('#product-name').val('');
        $('#product-description').val('');
        $('#product-image-id').val('0');
        $('#product-image-preview').html('');
        $('#product-remove-image').hide();
        $('#product-availability').val('available');
        $('.allergen-checkbox').prop('checked', false);
        $('.badge-checkbox').prop('checked', false);
        $('#product-prices-wrapper').html(createPriceRow());
    }

    function populateProductForm(product, prices) {
        $('#product-id').val(product.id);
        $('#product-category').val(product.category_id);
        $('#product-category-select').val(product.category_id);
        $('#product-name').val(product.name);
        $('#product-description').val(product.description || '');
        $('#product-availability').val(product.availability || 'available');
        $('#product-position').val(product.position || 0);

        // Handle image - show image and remove button if image_url exists and is not empty
        if (product.image_url && product.image_url !== '') {
            $('#product-image-id').val(product.image_id || 0);
            $('#product-image-preview').html('<img src="' + product.image_url + '" style="max-width: 200px; border-radius: 4px;">');
            $('#product-remove-image').show();
        } else {
            $('#product-image-id').val('0');
            $('#product-image-preview').html('');
            $('#product-remove-image').hide();
        }

        var allergensList = product.allergens ? product.allergens.split(',') : [];
        $('.allergen-checkbox').prop('checked', false);
        allergensList.forEach(function(allergen) {
            $('.allergen-checkbox[value="' + allergen.trim() + '"]').prop('checked', true);
        });

        var badgesList = product.badges ? product.badges.split(',') : [];
        $('.badge-checkbox').prop('checked', false);
        badgesList.forEach(function(badge) {
            $('.badge-checkbox[value="' + badge.trim() + '"]').prop('checked', true);
        });

        $('#product-prices-wrapper').html('');
        if (prices && prices.length > 0) {
            prices.forEach(function(price) {
                var isHappyHour = price.happy_hour == 1;
                var $row = $(createPriceRow(isHappyHour));
                $row.find('.price-label').val(price.label || '');
                $row.find('.price-amount').val(price.price);
                if (isHappyHour) {
                    $row.find('.price-happy-hour').prop('checked', true);
                }
                $('#product-prices-wrapper').append($row);
            });
        } else {
            $('#product-prices-wrapper').html(createPriceRow());
        }
    }

    // ========== ADMIN CATEGORY FILTERING (like frontend) ==========

    var filterWrapper = $('[data-admin-filter-wrapper]');
    if (filterWrapper.length > 0) {
        var rootTabs = filterWrapper.find('.lrob-admin-root-tab');
        var allProducts = $('#lrob-products-grid .lrob-product-card');

        // Initialize: show first root category on load
        if (rootTabs.length > 0) {
            var firstRootId = rootTabs.first().data('root-category');
            rootTabs.first().addClass('active');

            // Show level 1 filters for first category
            $('.lrob-level-1-filters[data-parent-id="' + firstRootId + '"]').show();

            // Filter products to show only those from first category
            filterProductsByRootCategory(firstRootId);
        }

        // Root category tab click
        rootTabs.on('click', function() {
            var rootCategoryId = $(this).data('root-category');

            // Update active tab
            rootTabs.removeClass('active');
            $(this).addClass('active');

            // Hide all level 1 and level 2 filters
            $('.lrob-level-1-filters, .lrob-level-2-filters').hide();

            // Deselect all badges
            $('.lrob-subcategory-badge').removeClass('active');

            // Show level 1 filters for this root category
            $('.lrob-level-1-filters[data-parent-id="' + rootCategoryId + '"]').show();

            // Filter products
            filterProductsByRootCategory(rootCategoryId);
        });

        // Level 1 subcategory badge click
        $(document).on('click', '.lrob-subcategory-badge[data-filter-level="1"]', function() {
            var subcategoryId = $(this).data('subcategory-id');
            var parentId = $(this).data('parent-id');
            var isActive = $(this).hasClass('active');

            if (isActive) {
                // Deselect
                $(this).removeClass('active');

                // Hide all level 2 filters
                $('.lrob-level-2-filters').hide();

                // Deselect all level 2 badges
                $('.lrob-subcategory-badge[data-filter-level="2"]').removeClass('active');

                // Show all products from root category
                filterProductsByRootCategory(parentId);
            } else {
                // Deselect all other level 1 badges with same parent
                $('.lrob-subcategory-badge[data-filter-level="1"][data-parent-id="' + parentId + '"]').removeClass('active');

                // Select this badge
                $(this).addClass('active');

                // Hide all level 2 filters first
                $('.lrob-level-2-filters').hide();

                // Deselect all level 2 badges
                $('.lrob-subcategory-badge[data-filter-level="2"]').removeClass('active');

                // Show level 2 filters for this subcategory (if any)
                $('.lrob-level-2-filters[data-parent-id="' + subcategoryId + '"]').show();

                // Filter products to this level 1 category
                filterProductsByCategory(subcategoryId, null);
            }
        });

        // Level 2 subcategory badge click
        $(document).on('click', '.lrob-subcategory-badge[data-filter-level="2"]', function() {
            var subcategoryId = $(this).data('subcategory-id');
            var parentId = $(this).data('parent-id');
            var isActive = $(this).hasClass('active');

            if (isActive) {
                // Deselect
                $(this).removeClass('active');

                // Show all products from parent (level 1) category
                filterProductsByCategory(parentId, null);
            } else {
                // Deselect all other level 2 badges with same parent
                $('.lrob-subcategory-badge[data-filter-level="2"][data-parent-id="' + parentId + '"]').removeClass('active');

                // Select this badge
                $(this).addClass('active');

                // Filter products to this level 2 category
                filterProductsByCategory(parentId, subcategoryId);
            }
        });

        function filterProductsByRootCategory(rootCategoryId) {
            allProducts.each(function() {
                var $product = $(this);
                var ancestors = $product.data('category-ancestors').toString().split(',');

                // Show product if any ancestor matches the root category
                if (ancestors.indexOf(rootCategoryId.toString()) !== -1) {
                    $product.show();
                } else {
                    $product.hide();
                }
            });
        }

        function filterProductsByCategory(level1CategoryId, level2CategoryId) {
            allProducts.each(function() {
                var $product = $(this);
                var categoryId = $product.data('category-id').toString();
                var ancestors = $product.data('category-ancestors').toString().split(',');

                if (level2CategoryId) {
                    // Level 2 filter: show only products from this specific category
                    if (ancestors.indexOf(level2CategoryId.toString()) !== -1) {
                        $product.show();
                    } else {
                        $product.hide();
                    }
                } else if (level1CategoryId) {
                    // Level 1 filter: show products from this category and all its children
                    if (ancestors.indexOf(level1CategoryId.toString()) !== -1) {
                        $product.show();
                    } else {
                        $product.hide();
                    }
                }
            });
        }
    }

    // ========== CATEGORIES ==========

    $('#lrob-add-category').on('click', function() {
        resetCategoryForm();
        $('#lrob-modal-title').text('Add Category');
        openModal('lrob-category-modal');
    });

    $('.lrob-edit-category').on('click', function() {
        var categoryId = $(this).data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_get_category',
                nonce: lrobCarte.nonce,
                id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    populateCategoryForm(response.data);
                    $('#lrob-modal-title').text('Edit Category');
                    openModal('lrob-category-modal');
                }
            }
        });
    });

    $('.lrob-delete-category').on('click', function() {
        if (!confirm('Are you sure you want to delete this category?')) return;

        var categoryId = $(this).data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_delete_category',
                nonce: lrobCarte.nonce,
                id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('.lrob-toggle-category').on('click', function() {
        var categoryId = $(this).data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_toggle_category',
                nonce: lrobCarte.nonce,
                id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Up/Down buttons for categories
    $('.lrob-move-category-up').on('click', function() {
        var $item = $(this).closest('.lrob-category-item');
        var $prev = $item.prev('.lrob-category-item');
        if ($prev.length) {
            $item.insertBefore($prev);
            updateCategoryPositions();
        }
    });

    $('.lrob-move-category-down').on('click', function() {
        var $item = $(this).closest('.lrob-category-item');
        var $next = $item.next('.lrob-category-item');
        if ($next.length) {
            $item.insertAfter($next);
            updateCategoryPositions();
        }
    });

    function updateCategoryPositions() {
        var positions = [];
        $('#lrob-categories-list .lrob-category-item').each(function(index) {
            positions.push($(this).data('id'));
        });

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_positions',
                nonce: lrobCarte.nonce,
                table: 'categories',
                positions: positions
            }
        });
    }

    // Indent / Unindent
    $('.lrob-indent-category').on('click', function() {
        var $item = $(this).closest('.lrob-category-item');
        var catId = $item.data('id');
        var $prev = $item.prev('.lrob-category-item');

        if (!$prev.length) {
            alert('Cannot create a subcategory here');
            return;
        }

        var newParentId = $prev.data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_category_parent',
                nonce: lrobCarte.nonce,
                id: catId,
                parent_id: newParentId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else if (response.data && response.data.message) {
                    alert(response.data.message);
                }
            }
        });
    });

    $('.lrob-unindent-category').on('click', function() {
        var $item = $(this).closest('.lrob-category-item');
        var catId = $item.data('id');
        var currentParent = $item.data('parent');

        // Find grandparent
        var $parentItem = $('.lrob-category-item[data-id="' + currentParent + '"]');
        var newParentId = $parentItem.length ? ($parentItem.data('parent') || 0) : 0;

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_update_category_parent',
                nonce: lrobCarte.nonce,
                id: catId,
                parent_id: newParentId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else if (response.data && response.data.message) {
                    alert(response.data.message);
                }
            }
        });
    });

    $('#lrob-category-form').on('submit', function(e) {
        e.preventDefault();

        var iconType = $('input[name="icon-type"]:checked').val();
        var iconValue;

        if (iconType === 'emoji') {
            iconValue = $('#category-emoji').val();
        } else if (iconType === 'image') {
            iconValue = $('#category-icon-image-id').val();
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
                id: $('#category-id').val(),
                parent_id: $('#category-parent').val(),
                name: $('#category-name').val(),
                slug: $('#category-slug').val(),
                icon_type: iconType,
                icon_value: iconValue,
                position: $('#category-position').val(),
                active: $('#category-active').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else if (response.data && response.data.message) {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Error saving');
            }
        });
    });

    $('input[name="icon-type"]').on('change', function() {
        var iconType = $(this).val();
        $('#icon-emoji-row, #icon-image-row').hide();

        if (iconType === 'emoji') {
            $('#icon-emoji-row').show();
        } else if (iconType === 'image') {
            $('#icon-image-row').show();
        }
    });

    $('.lrob-emoji-option').on('click', function() {
        $('#category-emoji').val($(this).data('emoji'));
    });

    // Media uploader for custom icon
    var categoryIconUploader;

    $('#category-upload-icon-image').on('click', function(e) {
        e.preventDefault();

        // Check if wp.media is available
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

        categoryIconUploader.on('select', function() {
            var attachment = categoryIconUploader.state().get('selection').first().toJSON();
            $('#category-icon-image-id').val(attachment.id);
            $('#category-icon-image-preview').html('<img src="' + attachment.url + '" style="max-width: 50px; max-height: 50px;">');
            $('#category-remove-icon-image').show();
        });

        categoryIconUploader.open();
    });

    $('#category-remove-icon-image').on('click', function() {
        $('#category-icon-image-id').val('');
        $('#category-icon-image-preview').html('');
        $(this).hide();
    });

    function resetCategoryForm() {
        $('#category-id').val('');
        $('#category-parent').val('0');
        $('#category-name').val('');
        $('#category-slug').val('');
        $('#category-emoji').val('🍽️');
        $('#category-icon-image-id').val('');
        $('#category-icon-image-preview').html('');
        $('#category-remove-icon-image').hide();
        $('#category-active').prop('checked', true);
        $('input[name="icon-type"][value="emoji"]').prop('checked', true).trigger('change');
    }

    function populateCategoryForm(category) {
        $('#category-id').val(category.id);
        $('#category-parent').val(category.parent_id || '0');
        $('#category-name').val(category.name);
        $('#category-slug').val(category.slug);
        $('#category-position').val(category.position);
        $('#category-active').prop('checked', category.active == 1);

        // Set icon type and value
        if (category.icon_type === 'emoji') {
            $('input[name="icon-type"][value="emoji"]').prop('checked', true);
            $('#category-emoji').val(category.icon_value || '🍽️');
        } else if (category.icon_type === 'image') {
            $('input[name="icon-type"][value="image"]').prop('checked', true);
            $('#category-icon-image-id').val(category.icon_value);

            if (category.icon_value) {
                $.ajax({
                    url: lrobCarte.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_ajax_get_attachment_url',
                        attachment_id: category.icon_value
                    },
                    success: function(url) {
                        if (url) {
                            $('#category-icon-image-preview').html('<img src="' + url + '" style="max-width: 50px; max-height: 50px;">');
                            $('#category-remove-icon-image').show();
                        }
                    }
                });
            }
        }

        $('input[name="icon-type"]:checked').trigger('change');
    }
});
