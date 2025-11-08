<?php
if (!defined('ABSPATH')) exit;

$display_mode = $attributes['displayMode'] ?? 'all';
$selected_category = $attributes['selectedCategory'] ?? 0;
$layout_style = $attributes['layoutStyle'] ?? 'compact';
$text_color = $attributes['textColor'] ?? '';
$border_color = $attributes['borderColor'] ?? '';
$accent_color = $attributes['accentColor'] ?? '';
$badge_bg_color = $attributes['badgeBgColor'] ?? '';
$badge_text_color = $attributes['badgeTextColor'] ?? '';
$card_border_radius = $attributes['cardBorderRadius'] ?? 12;
$card_border_width = $attributes['cardBorderWidth'] ?? 1;
$card_padding = $attributes['cardPadding'] ?? 20;
$card_gap = $attributes['cardGap'] ?? 30;
$font_size = $attributes['fontSize'] ?? 16;
$font_family = $attributes['fontFamily'] ?? 'system';
$show_images = $attributes['showImages'] ?? true;
$show_descriptions = $attributes['showDescriptions'] ?? true;
$show_allergens = $attributes['showAllergens'] ?? true;
$columns_desktop = $attributes['columnsDesktop'] ?? 2;
$columns_mobile = $attributes['columnsMobile'] ?? 1;

$out_of_stock_display = get_option('lrob_carte_out_of_stock_display', 'show');

$all_categories = $display_mode === 'single' && $selected_category
	? array(LRob_Carte_Database::get_category($selected_category))
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

$wrapper_attributes = get_block_wrapper_attributes(array(
	'class' => 'lrob-carte-wrapper lrob-layout-' . esc_attr($layout_style)
));

$resolved_font_family = $font_family === 'system'
	? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
	: $font_family;

$inline_style = "--lrob-cols-desktop: {$columns_desktop}; --lrob-cols-mobile: {$columns_mobile}; --lrob-border-radius: {$card_border_radius}px; --lrob-border-width: {$card_border_width}px; --lrob-card-padding: {$card_padding}px; --lrob-card-gap: {$card_gap}px; font-size: {$font_size}px; font-family: {$resolved_font_family};";

if ($text_color) $inline_style .= " color: {$text_color};";
if ($border_color) $inline_style .= " --lrob-border-color: {$border_color};";
if ($accent_color) $inline_style .= " --lrob-accent-color: {$accent_color};";
if ($badge_bg_color) $inline_style .= " --lrob-badge-bg: {$badge_bg_color};";
if ($badge_text_color) $inline_style .= " --lrob-badge-text: {$badge_text_color};";

$parent_categories = $categories_by_parent[0] ?? array();

if (!function_exists('lrob_render_subcategory_section')) {
	function lrob_render_subcategory_section($category, $categories_by_parent, $show_images, $show_descriptions, $show_allergens, $out_of_stock_display, $level = 1) {
		$has_children = isset($categories_by_parent[$category->id]) && !empty($categories_by_parent[$category->id]);
		$direct_products = LRob_Carte_Database::get_products($category->id);
		$all_products = LRob_Carte_Database::get_products_recursive($category->id);

		// Hide categories with no products (direct or in descendants)
		if (empty($all_products) && !$has_children) return;
		if (empty($all_products)) return;

		?>
		<div class="lrob-carte-subcategory" data-subcategory-id="<?php echo $category->id; ?>" data-level="<?php echo $level; ?>">
			<h<?php echo min($level + 3, 6); ?> class="lrob-carte-subcategory-title">
				<span class="lrob-carte-category-icon">
					<?php
					if ($category->icon_type === 'emoji') {
						echo esc_html($category->icon_value);
					} elseif ($category->icon_type === 'image' && $category->icon_value) {
						echo wp_get_attachment_image($category->icon_value, array(24, 24));
					} else {
						echo '<i class="' . esc_attr($category->icon_value) . '"></i>';
					}
					?>
				</span>
				<?php echo esc_html($category->name); ?>
			</h<?php echo min($level + 3, 6); ?>>

			<?php if (!empty($direct_products)): ?>
				<div class="lrob-carte-products">
					<?php foreach ($direct_products as $product):
						if ($product->availability !== 'available' && $out_of_stock_display === 'hide') continue;

						$prices = LRob_Carte_Database::get_product_prices($product->id);
						$is_unavailable = $product->availability !== 'available';
					?>
						<div class="lrob-carte-product <?php echo $is_unavailable ? 'lrob-unavailable' : ''; ?>">
							<div class="lrob-carte-product-content">
								<div class="lrob-carte-product-header">
									<h4 class="lrob-carte-product-name">
										<span class="lrob-carte-product-name-text"><?php echo esc_html($product->name); ?></span>
										<?php if ($is_unavailable): ?>
											<span class="lrob-carte-badge lrob-badge-unavailable"><?php _e('Out of Stock', 'lrob-la-carte'); ?></span>
										<?php endif; ?>
										<?php if ($product->badges): $badges = explode(',', $product->badges); foreach ($badges as $badge): ?>
											<span class="lrob-carte-badge lrob-badge-<?php echo esc_attr($badge); ?>"><?php echo esc_html(LRob_Carte_Settings::get_badges()[$badge] ?? $badge); ?></span>
										<?php endforeach; endif; ?>
									</h4>
									<div class="lrob-carte-product-main">
										<?php if ($show_images && $product->image_id): ?>
											<div class="lrob-carte-product-image"><?php echo wp_get_attachment_image($product->image_id, 'thumbnail', false, array('loading' => 'lazy')); ?></div>
										<?php endif; ?>
										<?php if ($show_descriptions && $product->description): ?>
											<p class="lrob-carte-product-description"><?php echo esc_html($product->description); ?></p>
										<?php endif; ?>
									</div>
									<?php if (!empty($prices)): ?>
										<div class="lrob-carte-product-prices">
											<?php foreach ($prices as $price): ?>
												<div class="lrob-carte-price <?php echo $price->happy_hour ? 'lrob-price-happy' : ''; ?>">
													<?php if ($price->label): ?><span class="lrob-carte-price-label"><?php echo esc_html($price->label); ?></span><?php endif; ?>
													<span class="lrob-carte-price-amount"><?php echo number_format($price->price, 2, ',', ' '); ?> €</span>
													<?php if ($price->happy_hour): ?><span class="lrob-price-happy-icon">🍹</span><?php endif; ?>
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
				<div class="lrob-carte-subcategories-container" data-parent-id="<?php echo $category->id; ?>">
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
		$all_products = LRob_Carte_Database::get_products_recursive($category->id);

		if (empty($all_products) && !$has_children) return;

		$is_root = $level === 0;
		?>
		<div class="lrob-carte-category <?php echo $is_root ? 'lrob-carte-root-category' : 'lrob-carte-child-category'; ?>"
			 data-category-id="<?php echo $category->id; ?>"
			 data-level="<?php echo $level; ?>">

			<div class="lrob-carte-category-header">
				<h<?php echo min($level + 2, 6); ?> class="lrob-carte-category-title">
					<span class="lrob-carte-category-icon">
						<?php
						if ($category->icon_type === 'emoji') {
							echo esc_html($category->icon_value);
						} elseif ($category->icon_type === 'image' && $category->icon_value) {
							echo wp_get_attachment_image($category->icon_value, array(32, 32));
						} else {
							echo '<i class="' . esc_attr($category->icon_value) . '"></i>';
						}
						?>
					</span>
					<?php echo esc_html($category->name); ?>
				</h<?php echo min($level + 2, 6); ?>>

				<?php if ($has_children): ?>
					<!-- Level 1 subcategory badges -->
					<div class="lrob-subcategory-filters lrob-level-1-filters" data-parent-id="<?php echo $category->id; ?>" data-filter-level="1">
						<?php foreach ($categories_by_parent[$category->id] as $child_cat):
							// Only show badge if category has products
							$child_products = LRob_Carte_Database::get_products_recursive($child_cat->id);
							if (empty($child_products)) continue;
						?>
							<button class="lrob-subcategory-badge"
									data-subcategory-id="<?php echo $child_cat->id; ?>"
									data-parent-id="<?php echo $category->id; ?>"
									data-filter-level="1">
								<span class="lrob-subcategory-badge-icon">
									<?php
									if ($child_cat->icon_type === 'emoji') {
										echo esc_html($child_cat->icon_value);
									} elseif ($child_cat->icon_type === 'image' && $child_cat->icon_value) {
										echo wp_get_attachment_image($child_cat->icon_value, array(16, 16));
									} else {
										echo '<i class="' . esc_attr($child_cat->icon_value) . '"></i>';
									}
									?>
								</span>
								<?php echo esc_html($child_cat->name); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<!-- Level 2 subcategory badges (initially hidden, shown when level 1 is selected) -->
					<?php foreach ($categories_by_parent[$category->id] as $child_cat):
						if (!isset($categories_by_parent[$child_cat->id]) || empty($categories_by_parent[$child_cat->id])) continue;

						// Check if any grandchildren have products
						$has_products_in_grandchildren = false;
						foreach ($categories_by_parent[$child_cat->id] as $grandchild_cat) {
							$grandchild_products = LRob_Carte_Database::get_products_recursive($grandchild_cat->id);
							if (!empty($grandchild_products)) {
								$has_products_in_grandchildren = true;
								break;
							}
						}
						if (!$has_products_in_grandchildren) continue;
					?>
						<div class="lrob-subcategory-filters lrob-level-2-filters"
							 data-parent-id="<?php echo $child_cat->id; ?>"
							 data-filter-level="2"
							 style="display: none;">
							<?php foreach ($categories_by_parent[$child_cat->id] as $grandchild_cat):
								// Only show badge if category has products
								$grandchild_products = LRob_Carte_Database::get_products_recursive($grandchild_cat->id);
								if (empty($grandchild_products)) continue;
							?>
								<button class="lrob-subcategory-badge"
										data-subcategory-id="<?php echo $grandchild_cat->id; ?>"
										data-parent-id="<?php echo $child_cat->id; ?>"
										data-filter-level="2">
									<span class="lrob-subcategory-badge-icon">
										<?php
										if ($grandchild_cat->icon_type === 'emoji') {
											echo esc_html($grandchild_cat->icon_value);
										} elseif ($grandchild_cat->icon_type === 'image' && $grandchild_cat->icon_value) {
											echo wp_get_attachment_image($grandchild_cat->icon_value, array(16, 16));
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
				<div class="lrob-carte-subcategories-wrapper" data-root-category-id="<?php echo $category->id; ?>">
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
				<button class="lrob-carte-nav-item" data-category="<?php echo $cat->id; ?>">
					<span class="lrob-carte-nav-icon">
						<?php
						if ($cat->icon_type === 'emoji') {
							echo esc_html($cat->icon_value);
						} elseif ($cat->icon_type === 'image' && $cat->icon_value) {
							echo wp_get_attachment_image($cat->icon_value, array(24, 24));
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
