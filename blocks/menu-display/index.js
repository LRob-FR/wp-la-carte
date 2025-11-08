(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps, PanelColorSettings } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, RangeControl } = wp.components;
    const { __ } = wp.i18n;

    registerBlockType('lrob-carte/menu-display', {
        edit: function (props) {
            const { attributes = {}, setAttributes } = props;

            // Safe destructuring with defaults
            const {
                displayMode = 'all',
                selectedCategory = 0,
                categoryNavPosition = 'top',
                layoutStyle = 'compact',
                textColor = '',
                borderColor = '',
                accentColor = '',
                badgeBgColor = '',
                badgeTextColor = '',
                cardBorderRadius = 12,
                cardBorderWidth = 1,
                cardPadding = 20,
                cardGap = 30,
                fontSize = 16,
                fontFamily = 'system',
                showImages = true,
                showDescriptions = true,
                showAllergens = true,
                columnsDesktop = 2,
                columnsMobile = 1
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

            // Sanitize localized categories data (just in case)
            const categories =
                (window.lrobCarteEditor?.categories || [])
                    .filter(cat =>
                        cat &&
                        typeof cat.label === 'string' &&
                        (typeof cat.value === 'number' || typeof cat.value === 'string')
                    );

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
                            label: __('Display Mode', 'lrob-la-carte'),
                            value: displayMode,
                            options: [
                                { label: __('Full Menu', 'lrob-la-carte'), value: 'all' },
                                { label: __('Single Category', 'lrob-la-carte'), value: 'single' }
                            ],
                            onChange: value => setAttributes({ displayMode: value })
                        }),
                        displayMode === 'single' &&
                            wp.element.createElement(SelectControl, {
                                label: __('Category', 'lrob-la-carte'),
                                value: selectedCategory,
                                options: categories.length
                                    ? categories
                                    : [{ label: __('Choose a category', 'lrob-la-carte'), value: 0 }],
                                onChange: value =>
                                    setAttributes({ selectedCategory: parseInt(value, 10) || 0 })
                            }),
                        displayMode === 'all' &&
                            wp.element.createElement(SelectControl, {
                                label: __('Navigation Position', 'lrob-la-carte'),
                                value: categoryNavPosition,
                                options: [
                                    { label: __('Top', 'lrob-la-carte'), value: 'top' },
                                    { label: __('Bottom', 'lrob-la-carte'), value: 'bottom' },
                                    { label: __('Top and Bottom', 'lrob-la-carte'), value: 'both' }
                                ],
                                onChange: value => setAttributes({ categoryNavPosition: value })
                            }),
                        wp.element.createElement(SelectControl, {
                            label: __('Layout Style', 'lrob-la-carte'),
                            value: layoutStyle,
                            options: [
                                { label: __('Compact (price on title line)', 'lrob-la-carte'), value: 'compact' },
                                { label: __('Classic (separate prices)', 'lrob-la-carte'), value: 'classic' }
                            ],
                            onChange: value => setAttributes({ layoutStyle: value })
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Images', 'lrob-la-carte'),
                            checked: showImages,
                            onChange: value => setAttributes({ showImages: value })
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Descriptions', 'lrob-la-carte'),
                            checked: showDescriptions,
                            onChange: value => setAttributes({ showDescriptions: value })
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Allergens', 'lrob-la-carte'),
                            checked: showAllergens,
                            onChange: value => setAttributes({ showAllergens: value })
                        })
                    ),

                    // Typography Panel
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Typography', 'lrob-la-carte'), initialOpen: false },
                        wp.element.createElement(RangeControl, {
                            label: __('Font Size (px)', 'lrob-la-carte'),
                            value: fontSize,
                            onChange: value => setAttributes({ fontSize: value }),
                            min: 12,
                            max: 24,
                            step: 1
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Font Family', 'lrob-la-carte'),
                            value: fontFamily,
                            options: fontFamilyOptions,
                            onChange: value => setAttributes({ fontFamily: value })
                        }),
                        wp.element.createElement(
                            'p',
                            { style: { fontSize: '13px', color: '#757575', margin: '8px 0 0' } },
                            __('Font size affects all text in the menu.', 'lrob-la-carte')
                        )
                    ),

                    // Card Style Panel
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Card Style', 'lrob-la-carte'), initialOpen: false },
                        wp.element.createElement(RangeControl, {
                            label: __('Corner Radius (px)', 'lrob-la-carte'),
                            value: cardBorderRadius,
                            onChange: value => setAttributes({ cardBorderRadius: value }),
                            min: 0,
                            max: 50
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Border Width (px)', 'lrob-la-carte'),
                            value: cardBorderWidth,
                            onChange: value => setAttributes({ cardBorderWidth: value }),
                            min: 0,
                            max: 5
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Inner Padding (px)', 'lrob-la-carte'),
                            value: cardPadding,
                            onChange: value => setAttributes({ cardPadding: value }),
                            min: 10,
                            max: 50
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Card Gap (px)', 'lrob-la-carte'),
                            value: cardGap,
                            onChange: value => setAttributes({ cardGap: value }),
                            min: 10,
                            max: 60
                        })
                    ),

                    // Columns Panel
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Columns', 'lrob-la-carte'), initialOpen: false },
                        wp.element.createElement(RangeControl, {
                            label: __('Columns (Desktop)', 'lrob-la-carte'),
                            value: columnsDesktop,
                            onChange: value => setAttributes({ columnsDesktop: value }),
                            min: 1,
                            max: 4
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Columns (Mobile)', 'lrob-la-carte'),
                            value: columnsMobile,
                            onChange: value => setAttributes({ columnsMobile: value }),
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
                                onChange: value => setAttributes({ textColor: value }),
                                label: __('Text Color', 'lrob-la-carte')
                            },
                            {
                                value: borderColor,
                                onChange: value => setAttributes({ borderColor: value }),
                                label: __('Border Color', 'lrob-la-carte')
                            },
                            {
                                value: accentColor,
                                onChange: value => setAttributes({ accentColor: value }),
                                label: __('Accent Color (prices)', 'lrob-la-carte')
                            },
                            {
                                value: badgeBgColor,
                                onChange: value => setAttributes({ badgeBgColor: value }),
                                label: __('Badge Background', 'lrob-la-carte')
                            },
                            {
                                value: badgeTextColor,
                                onChange: value => setAttributes({ badgeTextColor: value }),
                                label: __('Badge Text', 'lrob-la-carte')
                            }
                        ]
                    })
                ),

                // Editor Preview
                wp.element.createElement(
                    'div',
                    blockProps,
                    wp.element.createElement(
                        'div',
                        {
                            className: 'lrob-carte-preview',
                            style: {
                                fontFamily:
                                    fontFamily === 'system'
                                        ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
                                        : fontFamily,
                                fontSize: fontSize + 'px'
                            }
                        },
                        wp.element.createElement('div', { className: 'lrob-carte-icon' }, '🍽️'),
                        wp.element.createElement(
                            'p',
                            { style: { marginTop: '12px', fontSize: '14px' } },
                            displayMode === 'all'
                                ? __('The full menu will be displayed here.', 'lrob-la-carte')
                                : __('The selected category will be displayed here.', 'lrob-la-carte')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { marginTop: '8px', fontSize: '12px', color: '#757575' } },
                            __('Typography and color settings apply on the frontend.', 'lrob-la-carte')
                        )
                    )
                )
            );
        },

        save: function () {
            return null;
        }
    });
})(window.wp);
