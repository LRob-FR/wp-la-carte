(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps, PanelColorSettings } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, RangeControl } = wp.components;
    const { __ } = wp.i18n;

    registerBlockType('lrob-carte/menu-display', {
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const {
                displayMode, selectedCategory, categoryNavPosition, layoutStyle,
                textColor, borderColor, accentColor, badgeBgColor, badgeTextColor,
                cardBorderRadius, cardBorderWidth, cardPadding, cardGap,
                fontSize, fontFamily,
                showImages, showDescriptions, showAllergens,
                columnsDesktop, columnsMobile
            } = attributes;

            const blockProps = useBlockProps({
                className: 'lrob-carte-block-editor'
            });

            const fontFamilyOptions = [
                { label: __('System', 'lrob-la-carte'), value: 'system' },
                { label: 'Arial', value: 'Arial, sans-serif' },
                { label: 'Helvetica', value: 'Helvetica, sans-serif' },
                { label: 'Times New Roman', value: 'Times New Roman, serif' },
                { label: 'Georgia', value: 'Georgia, serif' },
                { label: 'Verdana', value: 'Verdana, sans-serif' },
                { label: 'Courier New', value: 'Courier New, monospace' },
                { label: 'Comic Sans MS', value: 'Comic Sans MS, cursive' },
                { label: 'Impact', value: 'Impact, sans-serif' },
                { label: 'Trebuchet MS', value: 'Trebuchet MS, sans-serif' }
            ];

            return wp.element.createElement(
                wp.element.Fragment,
                null,
                wp.element.createElement(
                    InspectorControls,
                    null,

                    // Display Panel
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Display', 'lrob-la-carte'), initialOpen: true },
                        wp.element.createElement(SelectControl, {
                            label: __('Mode d\'affichage', 'lrob-la-carte'),
                            value: displayMode,
                            options: [
                                { label: __('Full Menu', 'lrob-la-carte'), value: 'all' },
                                { label: __('Single Category', 'lrob-la-carte'), value: 'single' }
                            ],
                            onChange: function(value) { setAttributes({ displayMode: value }); }
                        }),
                        displayMode === 'single' && wp.element.createElement(SelectControl, {
                            label: __('Category', 'lrob-la-carte'),
                            value: selectedCategory,
                            options: window.lrobCarteEditor?.categories || [{ label: __('Choose a category', 'lrob-la-carte'), value: 0 }],
                            onChange: function(value) { setAttributes({ selectedCategory: parseInt(value) }); }
                        }),
                        displayMode === 'all' && wp.element.createElement(SelectControl, {
                            label: __('Navigation Position', 'lrob-la-carte'),
                            value: categoryNavPosition,
                            options: [
                                { label: __('Top', 'lrob-la-carte'), value: 'top' },
                                { label: __('Bottom', 'lrob-la-carte'), value: 'bottom' },
                                { label: __('Top and Bottom', 'lrob-la-carte'), value: 'both' }
                            ],
                            onChange: function(value) { setAttributes({ categoryNavPosition: value }); }
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Layout Style', 'lrob-la-carte'),
                            value: layoutStyle,
                            options: [
                                { label: __('Compact (price on title line)', 'lrob-la-carte'), value: 'compact' },
                                { label: __('Classic (separate prices)', 'lrob-la-carte'), value: 'classic' }
                            ],
                            onChange: function(value) { setAttributes({ layoutStyle: value }); }
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Images', 'lrob-la-carte'),
                            checked: showImages,
                            onChange: function(value) { setAttributes({ showImages: value }); }
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Descriptions', 'lrob-la-carte'),
                            checked: showDescriptions,
                            onChange: function(value) { setAttributes({ showDescriptions: value }); }
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Allergens', 'lrob-la-carte'),
                            checked: showAllergens,
                            onChange: function(value) { setAttributes({ showAllergens: value }); }
                        })
                    ),

                    // Typography Panel
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Typography', 'lrob-la-carte'), initialOpen: false },
                        wp.element.createElement(RangeControl, {
                            label: __('Taille de police (px)', 'lrob-la-carte'),
                            value: fontSize,
                            onChange: function(value) { setAttributes({ fontSize: value }); },
                            min: 12,
                            max: 24,
                            step: 1
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Police de caractères', 'lrob-la-carte'),
                            value: fontFamily,
                            options: fontFamilyOptions,
                            onChange: function(value) { setAttributes({ fontFamily: value }); }
                        }),
                        wp.element.createElement('p', { style: { fontSize: '13px', color: '#757575', margin: '8px 0 0' } },
                            __('La taille de police affecte tout le texte de la carte.', 'lrob-la-carte')
                        )
                    ),

                    // Panneau Style des cartes
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Style des cartes', 'lrob-la-carte'), initialOpen: false },
                        wp.element.createElement(RangeControl, {
                            label: __('Arrondi des coins (px)', 'lrob-la-carte'),
                            value: cardBorderRadius,
                            onChange: function(value) { setAttributes({ cardBorderRadius: value }); },
                            min: 0,
                            max: 50
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Épaisseur bordure (px)', 'lrob-la-carte'),
                            value: cardBorderWidth,
                            onChange: function(value) { setAttributes({ cardBorderWidth: value }); },
                            min: 0,
                            max: 5
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Padding interne (px)', 'lrob-la-carte'),
                            value: cardPadding,
                            onChange: function(value) { setAttributes({ cardPadding: value }); },
                            min: 10,
                            max: 50
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Espacement entre cartes (px)', 'lrob-la-carte'),
                            value: cardGap,
                            onChange: function(value) { setAttributes({ cardGap: value }); },
                            min: 10,
                            max: 60
                        })
                    ),

                    // Columns Panel
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Columns', 'lrob-la-carte'), initialOpen: false },
                        wp.element.createElement(RangeControl, {
                            label: __('Colonnes (Desktop)', 'lrob-la-carte'),
                            value: columnsDesktop,
                            onChange: function(value) { setAttributes({ columnsDesktop: value }); },
                            min: 1,
                            max: 4
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Colonnes (Mobile)', 'lrob-la-carte'),
                            value: columnsMobile,
                            onChange: function(value) { setAttributes({ columnsMobile: value }); },
                            min: 1,
                            max: 2
                        })
                    ),

                    // Colors Panel
                    wp.element.createElement(PanelColorSettings, {
                        title: __('Colors', 'lrob-la-carte'),
                        initialOpen: false,
                        colorSettings: [
                            {
                                value: textColor,
                                onChange: function(value) { setAttributes({ textColor: value }); },
                                label: __('Text Color', 'lrob-la-carte')
                            },
                            {
                                value: borderColor,
                                onChange: function(value) { setAttributes({ borderColor: value }); },
                                label: __('Border Color', 'lrob-la-carte')
                            },
                            {
                                value: accentColor,
                                onChange: function(value) { setAttributes({ accentColor: value }); },
                                label: __('Couleur d\'accent (prix)', 'lrob-la-carte')
                            },
                            {
                                value: badgeBgColor,
                                onChange: function(value) { setAttributes({ badgeBgColor: value }); },
                                label: __('Badge Background', 'lrob-la-carte')
                            },
                            {
                                value: badgeTextColor,
                                onChange: function(value) { setAttributes({ badgeTextColor: value }); },
                                label: __('Badge Text', 'lrob-la-carte')
                            }
                        ]
                    })
                ),

                // Aperçu dans l'éditeur
                wp.element.createElement(
                    'div',
                    blockProps,
                    wp.element.createElement(
                        'div',
                        {
                            className: 'lrob-carte-preview',
                            style: {
                                fontFamily: fontFamily === 'system' ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif' : fontFamily,
                                fontSize: fontSize + 'px'
                            }
                        },
                        wp.element.createElement('div', { className: 'lrob-carte-icon' }, '🍽️'),
                        wp.element.createElement(
                            'p',
                            { style: { marginTop: '12px', fontSize: '14px' } },
                            displayMode === 'all'
                                ? __('Toute la carte sera affichée ici', 'lrob-la-carte')
                                : __('La catégorie sélectionnée sera affichée ici', 'lrob-la-carte')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { marginTop: '8px', fontSize: '12px', color: '#757575' } },
                            __('Les réglages de typographie et couleurs seront appliqués sur le frontend.', 'lrob-la-carte')
                        )
                    )
                )
            );
        },

        save: function() {
            return null;
        }
    });
})(window.wp);
