<?php
if (!defined('ABSPATH')) exit;

$display_mode       = $attributes['displayMode'] ?? 'all';
$selected_category  = $attributes['selectedCategory'] ?? 0;
$layout_style       = $attributes['layoutStyle'] ?? 'compact';
$text_color         = $attributes['textColor'] ?? '';
$border_color       = $attributes['borderColor'] ?? '';
$accent_color       = $attributes['accentColor'] ?? '';
$badge_bg_color     = $attributes['badgeBgColor'] ?? '';
$badge_text_color   = $attributes['badgeTextColor'] ?? '';
$card_border_radius = $attributes['cardBorderRadius'] ?? 12;
$card_border_width  = $attributes['cardBorderWidth'] ?? 1;
$card_padding       = $attributes['cardPadding'] ?? 20;
$card_gap           = $attributes['cardGap'] ?? 30;
$font_size          = $attributes['fontSize'] ?? 16;
$font_family        = $attributes['fontFamily'] ?? 'system';
$show_images        = $attributes['showImages'] ?? true;
$show_descriptions  = $attributes['showDescriptions'] ?? true;
$show_allergens     = $attributes['showAllergens'] ?? true;
$columns_desktop    = $attributes['columnsDesktop'] ?? 2;
$columns_mobile     = $attributes['columnsMobile'] ?? 1;

$out_of_stock_display = get_option('lrob_carte_out_of_stock_display', 'show');

$all_categories = ($display_mode === 'single' && $selected_category)
	? array(LRob_Carte_Database::get_category((int) $selected_category))
	: LRob_Carte_Database::get_categories('position', 'ASC', true);

$categories_by_parent = array();
foreach ($all_categories as $cat) {
	if (!$cat) continue;
	$parent_id = $cat->parent_id ?? 0;
	if (!isset($categories_by_parent[$parent_id])) {
		$categories_by_parent[$parent_id] = array();
	}
	$categories_by_parent[$parent_id][] = $cat;
}

/** minimal sanitization for class fragment to avoid odd characters */
$layout_style = preg_replace('/[^a-z0-9_-]/i', '', (string) $layout_style) ?: 'compact';

$wrapper_attributes = get_block_wrapper_attributes(array(
	'class' => 'lrob-carte-wrapper lrob-layout-' . $layout_style
));

$resolved_font_family = $font_family === 'system'
	? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
	: preg_replace('/[^A-Za-z0-9 ,"\-]+/', '', (string) $font_family);

/** build inline CSS safely */
$inline_style = sprintf(
	'--lrob-cols-desktop:%d; --lrob-cols-mobile:%d; --lrob-border-radius:%dpx; --lrob-border-width:%dpx; --lrob-card-padding:%dpx; --lrob-card-gap:%dpx; font-size:%dpx; font-family:%s;',
	(int) $columns_desktop,
	(int) $columns_mobile,
	(int) $card_border_radius,
	(int) $card_border_width,
	(int) $card_padding,
	(int) $card_gap,
	(int) $font_size,
	esc_attr($resolved_font_family)
);

$to_css_color = static function($v){ $c = sanitize_hex_color($v); return $c ? $c : ''; };

if ($text_color)       { $c = $to_css_color($text_color);       if ($c) $inline_style .= " color: {$c};"; }
if ($border_color)     { $c = $to_css_color($border_color);     if ($c) $inline_style .= " --lrob-border-color: {$c};"; }
if ($accent_color)     { $c = $to_css_color($accent_color);     if ($c) $inline_style .= " --lrob-accent-color: {$c};"; }
if ($badge_bg_color)   { $c = $to_css_color($badge_bg_color);   if ($c) $inline_style .= " --lrob-badge-bg: {$c};"; }
if ($badge_text_color) { $c = $to_css_color($badge_text_color); if ($c) $inline_style .= " --lrob-badge-text: {$c};"; }

$parent_categories = $categories_by_parent[0] ?? array();

if (!function_exists('lrob_render_subcategory_section')) {
	function lrob_render_subcategory_section($category, $categories_by_parent, $show_images, $show_descriptions, $show_allergens, $out_of_stock_display, $level = 1) {
		$has_children   = isset($categories_by_parent[$category->id]) && !empty($categories_by_parent[$category->id]);
		$direct_products = LRob_Carte_Database::get_products((int) $category->id);
		$all_products    = LRob_Carte_Database::get_products_recursive((int) $category->id);

		// Hide categories with no products (direct or in descendants)
		if (empty($all_products) && !$has_children) return;
		if (empty($all_products)) return;

		$h = (int) min(6, max(2, $level + 3));
		?>
		<div class="lrob-carte-subcategory" data-subcategory-id="<?php echo esc_attr($category->id); ?>" data-level="<?php echo esc_attr((int)$level); ?>">
			<h<?php echo $h; ?> class="lrob-carte-subcategory-title">
				<span class="lrob-carte-category-icon">
					<?php
					if ($category->icon_type === 'emoji') {
						echo esc_html($category->icon_value);
					} elseif ($category->icon_type === 'image' && $category->icon_value) {
						echo wp_get_attachment_image((int) $category->icon_value, array(24, 24));
					} else {
						echo '<i class="' . esc_attr($category->icon_value) . '"></i>';
					}
					?>
				</span>
				<?php echo esc_html($category->name); ?>
			</h<?php echo $h; ?>>

			<?php if (!empty($direct_products)): ?>
				<div class="lrob-carte-products">
					<?php foreach ($direct_products as $product):
						if ($product->availability !== 'available' && $out_of_stock_display === 'hide') continue;

						$prices = LRob_Carte_Database::get_product_prices((int) $product->id);
						$is_unavailable = $product->availability !== 'available';
					?>
						<div class="lrob-carte-product <?php echo $is_unavailable ? 'lrob-unavailable' : ''; ?>">
							<div class="lrob-carte-product-content">
								<div class="lrob-carte-product-header">
									<h4 class="lrob-carte-product-name">
										<span class="lrob-carte-product-name-text"><?php echo esc_html($product->name); ?></span>
										<?php if ($is_unavailable): ?>
											<span class="lrob-carte-badge lrob-badge-unavailable"><?php esc_html_e('Out of Stock', 'lrob-la-carte'); ?></span>
										<?php endif; ?>
										<?php if ($product->badges): $badges = explode(',', $product->badges); foreach ($badges as $badge): ?>
											<span class="lrob-carte-badge lrob-badge-<?php echo esc_attr($badge); ?>"><?php echo esc_html(LRob_Carte_Settings::get_badges()[$badge] ?? $badge); ?></span>
										<?php endforeach; endif; ?>
									</h4>
									<div class="lrob-carte-product-main">
										<?php if ($show_images && $product->image_id): ?>
											<div class="lrob-carte-product-image"><?php echo wp_get_attachment_image((int) $product->image_id, 'thumbnail', false, array('loading' => 'lazy')); ?></div>
										<?php endif; ?>
										<?php if ($show_descriptions && $product->description): ?>
											<p class="lrob-carte-product-description"><?php echo esc_html($product->description); ?></p>
										<?php endif; ?>
									</div>
									<?php if (!empty($prices)): ?>
										<div class="lrob-carte-product-prices">
											<?php foreach ($prices as $price): ?>
												<div class="lrob-carte-price <?php echo !empty($price->happy_hour) ? 'lrob-price-happy' : ''; ?>">
													<?php if ($price->label): ?><span class="lrob-carte-price-label"><?php echo esc_html($price->label); ?></span><?php endif; ?>
													<span class="lrob-carte-price-amount"><?php echo esc_html(number_format((float) $price->price, 2, ',', ' ')); ?> €</span>
													<?php if (!empty($price->happy_hour)): ?><span class="lrob-price-happy-icon">🍹</span><?php endif; ?>
												</div>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>

								<?php if ($show_allergens && $product->allergens): ?>
									<div class="lrob-carte-product-allergens">
										<small>⚠️ <?php echo esc_html($product->allergens); ?></small>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ($has_children): ?>
				<div class="lrob-carte-subcategories-container" data-parent-id="<?php echo esc_attr($category->id); ?>">
					<?php
					foreach ($categories_by_parent[$category->id] as $child_cat) {
						lrob_render_subcategory_section($child_cat, $categories_by_parent, $show_images, $show_descriptions, $show_allergens, $out_of_stock_display, $level + 1);
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

if (!function_exists('lrob_render_category_drill_down')) {
	function lrob_render_category_drill_down($category, $categories_by_parent, $show_images, $show_descriptions, $show_allergens, $out_of_stock_display, $level = 0) {
		$has_children = isset($categories_by_parent[$category->id]) && !empty($categories_by_parent[$category->id]);
		$all_products = LRob_Carte_Database::get_products_recursive((int) $category->id);

		if (empty($all_products) && !$has_children) return;

		$is_root = ($level === 0);
		$h = (int) min(6, max(2, $level + 2));
		?>
		<div class="lrob-carte-category <?php echo $is_root ? 'lrob-carte-root-category' : 'lrob-carte-child-category'; ?>"
			 data-category-id="<?php echo esc_attr($category->id); ?>"
			 data-level="<?php echo esc_attr((int)$level); ?>">

			<div class="lrob-carte-category-header">
				<h<?php echo $h; ?> class="lrob-carte-category-title">
					<span class="lrob-carte-category-icon">
						<?php
						if ($category->icon_type === 'emoji') {
							echo esc_html($category->icon_value);
						} elseif ($category->icon_type === 'image' && $category->icon_value) {
							echo wp_get_attachment_image((int) $category->icon_value, array(32, 32));
						} else {
							echo '<i class="' . esc_attr($category->icon_value) . '"></i>';
						}
						?>
					</span>
					<?php echo esc_html($category->name); ?>
				</h<?php echo $h; ?>>

				<?php if ($has_children): ?>
					<!-- Level 1 subcategory badges -->
					<div class="lrob-subcategory-filters lrob-level-1-filters" data-parent-id="<?php echo esc_attr($category->id); ?>" data-filter-level="1">
						<?php foreach ($categories_by_parent[$category->id] as $child_cat):
							$child_products = LRob_Carte_Database::get_products_recursive((int) $child_cat->id);
							if (empty($child_products)) continue;
						?>
							<button class="lrob-subcategory-badge"
									data-subcategory-id="<?php echo esc_attr($child_cat->id); ?>"
									data-parent-id="<?php echo esc_attr($category->id); ?>"
									data-filter-level="1">
								<span class="lrob-subcategory-badge-icon">
									<?php
									if ($child_cat->icon_type === 'emoji') {
										echo esc_html($child_cat->icon_value);
									} elseif ($child_cat->icon_type === 'image' && $child_cat->icon_value) {
										echo wp_get_attachment_image((int) $child_cat->icon_value, array(16, 16));
									} else {
										echo '<i class="' . esc_attr($child_cat->icon_value) . '"></i>';
									}
									?>
								</span>
								<?php echo esc_html($child_cat->name); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<!-- Level 2 subcategory badges (initially hidden) -->
					<?php foreach ($categories_by_parent[$category->id] as $child_cat):
						if (empty($categories_by_parent[$child_cat->id])) continue;

						$has_products_in_grandchildren = false;
						foreach ($categories_by_parent[$child_cat->id] as $grandchild_cat) {
							$grandchild_products = LRob_Carte_Database::get_products_recursive((int) $grandchild_cat->id);
							if (!empty($grandchild_products)) {
								$has_products_in_grandchildren = true;
								break;
							}
						}
						if (!$has_products_in_grandchildren) continue;
					?>
						<div class="lrob-subcategory-filters lrob-level-2-filters"
							 data-parent-id="<?php echo esc_attr($child_cat->id); ?>"
							 data-filter-level="2"
							 style="display: none;">
							<?php foreach ($categories_by_parent[$child_cat->id] as $grandchild_cat):
								$grandchild_products = LRob_Carte_Database::get_products_recursive((int) $grandchild_cat->id);
								if (empty($grandchild_products)) continue;
							?>
								<button class="lrob-subcategory-badge"
										data-subcategory-id="<?php echo esc_attr($grandchild_cat->id); ?>"
										data-parent-id="<?php echo esc_attr($child_cat->id); ?>"
										data-filter-level="2">
									<span class="lrob-subcategory-badge-icon">
										<?php
										if ($grandchild_cat->icon_type === 'emoji') {
											echo esc_html($grandchild_cat->icon_value);
										} elseif ($grandchild_cat->icon_type === 'image' && $grandchild_cat->icon_value) {
											echo wp_get_attachment_image((int) $grandchild_cat->icon_value, array(16, 16));
										} else {
											echo '<i class="' . esc_attr($grandchild_cat->icon_value) . '"></i>';
										}
										?>
									</span>
									<?php echo esc_html($grandchild_cat->name); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- Subcategories with their products -->
			<?php if ($has_children): ?>
				<div class="lrob-carte-subcategories-wrapper" data-root-category-id="<?php echo esc_attr($category->id); ?>">
					<?php
					foreach ($categories_by_parent[$category->id] as $child_cat) {
						lrob_render_subcategory_section($child_cat, $categories_by_parent, $show_images, $show_descriptions, $show_allergens, $out_of_stock_display, 1);
					}
					?>
				</div>
			<?php endif; ?>
		</div>
	<?php
	}
}
?>

<div <?php echo $wrapper_attributes; ?> style="<?php echo esc_attr($inline_style); ?>" data-carte-wrapper>

	<?php if ($display_mode === 'all' && count($parent_categories) > 1): ?>
		<div class="lrob-carte-nav">
			<?php foreach ($parent_categories as $cat): ?>
				<button class="lrob-carte-nav-item" data-category="<?php echo esc_attr($cat->id); ?>">
					<span class="lrob-carte-nav-icon">
						<?php
						if ($cat->icon_type === 'emoji') {
							echo esc_html($cat->icon_value);
						} elseif ($cat->icon_type === 'image' && $cat->icon_value) {
							echo wp_get_attachment_image((int) $cat->icon_value, array(24, 24));
						} else {
							echo '<i class="' . esc_attr($cat->icon_value) . '"></i>';
						}
						?>
					</span>
					<span class="lrob-carte-nav-label"><?php echo esc_html($cat->name); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	foreach ($parent_categories as $parent_cat) {
		lrob_render_category_drill_down($parent_cat, $categories_by_parent, $show_images, $show_descriptions, $show_allergens, $out_of_stock_display, 0);
	}
	?>

</div>
