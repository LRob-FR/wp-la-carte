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
        $('#lrob-modal-title').text('Ajouter un produit');
        openModal('lrob-product-modal');
    });
    
    // Synchroniser le select de catégorie avec le champ caché
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
                    $('#lrob-modal-title').text('Modifier le produit');
                    openModal('lrob-product-modal');
                }
            }
        });
    });

    $('.lrob-delete-product').on('click', function() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) return;
        
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

    // Boutons Haut/Bas pour les produits
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
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: 'Choisir une image',
            button: { text: 'Utiliser cette image' },
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

    // Price management avec suggestions
    var usedPriceLabels = new Set();
    
    // Collecter les labels existants au chargement
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
    
    function updatePriceLabelsSuggestions() {
        var existingDatalist = $('#price-labels-suggestions');
        if (existingDatalist.length === 0) {
            $('body').append('<datalist id="price-labels-suggestions"></datalist>');
            existingDatalist = $('#price-labels-suggestions');
        }
        
        existingDatalist.empty();
        usedPriceLabels.forEach(function(label) {
            existingDatalist.append('<option value="' + label + '">');
        });
    }
    
    $(document).on('blur', '.price-label', function() {
        var label = $(this).val().trim();
        if (label) {
            usedPriceLabels.add(label);
            updatePriceLabelsSuggestions();
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
    
    $('#lrob-add-price').on('click', function() {
        var newRow = createPriceRow(false);
        $('#product-prices-wrapper').append(newRow);
        updatePriceLabelsSuggestions();
    });

    $(document).on('click', '.lrob-remove-price', function() {
        if ($('#product-prices-wrapper .lrob-price-row').length > 1) {
            $(this).closest('.lrob-price-row').remove();
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
        $('.allergen-checkbox, .badge-checkbox').prop('checked', false);
        
        $('#product-prices-wrapper').html(createPriceRow(false));
        updatePriceLabelsSuggestions();
    }

    function populateProductForm(product, prices) {
        $('#product-id').val(product.id);
        $('#product-category').val(product.category_id);
        $('#product-category-select').val(product.category_id);
        $('#product-name').val(product.name);
        $('#product-description').val(product.description);
        $('#product-image-id').val(product.image_id || '0');
        $('#product-availability').val(product.availability);
        $('#product-position').val(product.position);

        if (product.image_id) {
            $.ajax({
                url: lrobCarte.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_ajax_get_attachment_url',
                    attachment_id: product.image_id
                },
                success: function(url) {
                    if (url) {
                        $('#product-image-preview').html('<img src="' + url + '">');
                        $('#product-remove-image').show();
                    }
                }
            });
        }

        if (product.allergens) {
            var allergensList = product.allergens.split(',');
            allergensList.forEach(function(allergen) {
                $('.allergen-checkbox[value="' + allergen + '"]').prop('checked', true);
            });
        }

        if (product.badges) {
            var badgesList = product.badges.split(',');
            badgesList.forEach(function(badge) {
                $('.badge-checkbox[value="' + badge + '"]').prop('checked', true);
            });
        }

        $('#product-prices-wrapper').html('');
        if (prices.length > 0) {
            prices.forEach(function(price) {
                var isHappyHour = price.happy_hour == 1;
                var row = createPriceRow(isHappyHour);
                var $row = $(row);
                $row.find('.price-label').val(price.label || '');
                $row.find('.price-amount').val(price.price);
                $('#product-prices-wrapper').append($row);
            });
        } else {
            $('#product-prices-wrapper').html(createPriceRow(false));
        }
        
        updatePriceLabelsSuggestions();
    }

    // ========== CATEGORIES ==========

    $('#lrob-add-category').on('click', function() {
        resetCategoryForm();
        $('#lrob-category-modal-title').text('Ajouter une catégorie');
        openModal('lrob-category-modal');
    });

    $('.lrob-edit-category').on('click', function() {
        var catId = $(this).data('id');

        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_get_category',
                nonce: lrobCarte.nonce,
                id: catId
            },
            success: function(response) {
                if (response.success) {
                    populateCategoryForm(response.data);
                    $('#lrob-category-modal-title').text('Modifier la catégorie');
                    openModal('lrob-category-modal');
                }
            }
        });
    });

    $('.lrob-delete-category').on('click', function() {
        if (!confirm('Êtes-vous sûr ? Tous les produits de cette catégorie seront supprimés.')) return;
        
        var catId = $(this).data('id');
        
        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_delete_category',
                nonce: lrobCarte.nonce,
                id: catId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('.lrob-toggle-category').on('click', function() {
        var $btn = $(this);
        var catId = $btn.data('id');
        var isActive = $btn.data('active');
        
        $.ajax({
            url: lrobCarte.ajaxurl,
            type: 'POST',
            data: {
                action: 'lrob_toggle_category',
                nonce: lrobCarte.nonce,
                id: catId,
                active: isActive
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else if (response.data && response.data.message) {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Erreur lors de la mise à jour');
            }
        });
    });

    // Boutons Haut/Bas pour les catégories
    $('.lrob-move-category-up').on('click', function() {
        var $item = $(this).closest('.lrob-category-item');
        var catId = $item.data('id');
        var level = $item.data('level');
        
        // Trouver l'élément précédent de même niveau
        var $prev = null;
        var $check = $item.prev('.lrob-category-item');
        
        while ($check.length) {
            if ($check.data('level') < level) {
                // On a atteint un niveau supérieur, pas de déplacement possible
                break;
            }
            if ($check.data('level') === level) {
                $prev = $check;
                break;
            }
            $check = $check.prev('.lrob-category-item');
        }
        
        if ($prev) {
            // Collecter l'élément et tous ses enfants
            var $toMove = $item;
            var $children = $();
            var $next = $item.next('.lrob-category-item');
            
            while ($next.length && $next.data('level') > level) {
                $children = $children.add($next);
                $next = $next.next('.lrob-category-item');
            }
            
            // Déplacer l'élément parent et ses enfants avant le précédent de même niveau
            $prev.before($toMove);
            if ($children.length) {
                $toMove.after($children);
            }
            
            updateCategoryPositions();
        }
    });

    $('.lrob-move-category-down').on('click', function() {
        var $item = $(this).closest('.lrob-category-item');
        var catId = $item.data('id');
        var level = $item.data('level');
        
        // Collecter tous les enfants de cet élément
        var $children = $();
        var $check = $item.next('.lrob-category-item');
        
        while ($check.length && $check.data('level') > level) {
            $children = $children.add($check);
            $check = $check.next('.lrob-category-item');
        }
        
        // Trouver l'élément suivant de même niveau (après tous les enfants)
        var $next = null;
        $check = $children.length ? $children.last().next('.lrob-category-item') : $item.next('.lrob-category-item');
        
        while ($check.length) {
            if ($check.data('level') < level) {
                // On a atteint un niveau supérieur, pas de déplacement possible
                break;
            }
            if ($check.data('level') === level) {
                $next = $check;
                break;
            }
            $check = $check.next('.lrob-category-item');
        }
        
        if ($next) {
            // Collecter les enfants du suivant
            var $nextChildren = $();
            $check = $next.next('.lrob-category-item');
            
            while ($check.length && $check.data('level') > level) {
                $nextChildren = $nextChildren.add($check);
                $check = $check.next('.lrob-category-item');
            }
            
            // Déplacer après le suivant et tous ses enfants
            var $insertAfter = $nextChildren.length ? $nextChildren.last() : $next;
            $insertAfter.after($item);
            if ($children.length) {
                $item.after($children);
            }
            
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
            alert('Impossible de créer une sous-catégorie ici');
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
        
        // Trouver le grand-parent
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
        } else {
            iconValue = $('#category-fa').val();
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
                alert('Erreur lors de l\'enregistrement');
            }
        });
    });

    $('input[name="icon-type"]').on('change', function() {
        var iconType = $(this).val();
        $('#icon-emoji-row, #icon-image-row, #icon-fa-row').hide();
        
        if (iconType === 'emoji') {
            $('#icon-emoji-row').show();
        } else if (iconType === 'image') {
            $('#icon-image-row').show();
        } else {
            $('#icon-fa-row').show();
        }
    });

    $('.lrob-emoji-option').on('click', function() {
        $('#category-emoji').val($(this).data('emoji'));
    });

    // Media uploader pour icône personnalisée
    var categoryIconUploader;
    
    $('#category-upload-icon-image').on('click', function(e) {
        e.preventDefault();
        
        if (categoryIconUploader) {
            categoryIconUploader.open();
            return;
        }
        
        categoryIconUploader = wp.media({
            title: 'Choisir une icône',
            button: { text: 'Utiliser cette image' },
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
        $('#category-fa').val('');
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

        // Définir le type d'icône et la valeur
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
        } else {
            $('input[name="icon-type"][value="fa"]').prop('checked', true);
            $('#category-fa').val(category.icon_value);
        }
        
        $('input[name="icon-type"]:checked').trigger('change');
    }

    // Product search
    $('#lrob-product-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        var $products = $('#lrob-products-list .lrob-product-item');
        var visibleCount = 0;

        if (searchTerm === '') {
            $products.show();
            $('.lrob-search-count').text('');
            return;
        }

        $products.each(function() {
            var $product = $(this);
            var name = $product.find('.lrob-product-name').text().toLowerCase();
            var desc = $product.find('.lrob-product-desc').text().toLowerCase();
            var allergens = $product.find('.lrob-product-allergens').text().toLowerCase();

            if (name.indexOf(searchTerm) !== -1 || desc.indexOf(searchTerm) !== -1 || allergens.indexOf(searchTerm) !== -1) {
                $product.show();
                visibleCount++;
            } else {
                $product.hide();
            }
        });

        var totalCount = $products.length;
        $('.lrob-search-count').text(visibleCount + ' / ' + totalCount);
    });
});
