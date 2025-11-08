document.addEventListener('DOMContentLoaded', function() {
    const carteWrappers = document.querySelectorAll('[data-carte-wrapper]');

    carteWrappers.forEach(wrapper => {
        const navItems = wrapper.querySelectorAll('.lrob-carte-nav-item');
        const rootCategories = wrapper.querySelectorAll('.lrob-carte-root-category');

        // Initialize: show first category if multiple root categories exist
        if (navItems.length > 0 && rootCategories.length > 1) {
            rootCategories.forEach((cat, index) => {
                if (index !== 0) {
                    cat.style.display = 'none';
                }
            });

            navItems[0].classList.add('active');

            // Root navigation
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const categoryId = this.getAttribute('data-category');

                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');

                    rootCategories.forEach(cat => {
                        if (cat.getAttribute('data-category-id') === categoryId) {
                            cat.style.display = 'block';
                            resetCategoryFilters(cat);
                        } else {
                            cat.style.display = 'none';
                        }
                    });

                    this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });
        }

        // Hierarchical subcategory filtering
        const subcategoryBadges = wrapper.querySelectorAll('.lrob-subcategory-badge');

        subcategoryBadges.forEach(badge => {
            badge.addEventListener('click', function(e) {
                e.preventDefault();

                const subcategoryId = this.getAttribute('data-subcategory-id');
                const parentId = this.getAttribute('data-parent-id');
                const filterLevel = parseInt(this.getAttribute('data-filter-level'));
                const isActive = this.classList.contains('active');

                // Find the root category element
                const rootCategory = this.closest('.lrob-carte-root-category');
                if (!rootCategory) return;

                const rootCategoryId = rootCategory.getAttribute('data-category-id');

                if (filterLevel === 1) {
                    // Level 1 badge clicked
                    handleLevel1BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive);
                } else if (filterLevel === 2) {
                    // Level 2 badge clicked
                    handleLevel2BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive);
                }
            });
        });

        function handleLevel1BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive) {
            // Get all level 1 badges in this root category
            const level1Badges = rootCategory.querySelectorAll('.lrob-subcategory-badge[data-filter-level="1"]');

            // Get all level 2 filter containers in this root category
            const level2Filters = rootCategory.querySelectorAll('.lrob-level-2-filters');

            if (isActive) {
                // Deselect this badge
                level1Badges.forEach(b => b.classList.remove('active'));

                // Hide all level 2 filters
                level2Filters.forEach(f => f.style.display = 'none');

                // Show all subcategories in root category
                filterSubcategories(rootCategory, null, null);
            } else {
                // Deselect all other level 1 badges
                level1Badges.forEach(b => b.classList.remove('active'));

                // Select this badge
                const clickedBadge = rootCategory.querySelector(`.lrob-subcategory-badge[data-subcategory-id="${subcategoryId}"][data-filter-level="1"]`);
                if (clickedBadge) clickedBadge.classList.add('active');

                // Hide all level 2 filters first
                level2Filters.forEach(f => {
                    f.style.display = 'none';
                    // Deselect all level 2 badges
                    f.querySelectorAll('.lrob-subcategory-badge').forEach(b => b.classList.remove('active'));
                });

                // Show level 2 filters for this subcategory (if any)
                const level2FilterContainer = rootCategory.querySelector(`.lrob-level-2-filters[data-parent-id="${subcategoryId}"]`);
                if (level2FilterContainer) {
                    level2FilterContainer.style.display = 'flex';
                }

                // Filter to show only this level 1 category and its children
                filterSubcategories(rootCategory, subcategoryId, null);
            }
        }

        function handleLevel2BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive) {
            // Get all level 2 badges in the same parent
            const level2Container = rootCategory.querySelector(`.lrob-level-2-filters[data-parent-id="${parentId}"]`);
            if (!level2Container) return;

            const level2Badges = level2Container.querySelectorAll('.lrob-subcategory-badge[data-filter-level="2"]');

            if (isActive) {
                // Deselect this badge
                level2Badges.forEach(b => b.classList.remove('active'));

                // Show all subcategories under the parent (level 1) category
                filterSubcategories(rootCategory, parentId, null);
            } else {
                // Deselect all other level 2 badges
                level2Badges.forEach(b => b.classList.remove('active'));

                // Select this badge
                const clickedBadge = level2Container.querySelector(`.lrob-subcategory-badge[data-subcategory-id="${subcategoryId}"]`);
                if (clickedBadge) clickedBadge.classList.add('active');

                // Filter to show only this level 2 category
                filterSubcategories(rootCategory, parentId, subcategoryId);
            }
        }

        function filterSubcategories(rootCategory, level1CategoryId, level2CategoryId) {
            // Get all direct subcategory sections (level 1)
            const subcategoriesWrapper = rootCategory.querySelector('.lrob-carte-subcategories-wrapper');
            if (!subcategoriesWrapper) return;

            const allLevel1Subcategories = subcategoriesWrapper.querySelectorAll(':scope > .lrob-carte-subcategory');

            if (!level1CategoryId && !level2CategoryId) {
                // No filter: show all level 1 subcategories and their children
                allLevel1Subcategories.forEach(subcat => {
                    subcat.style.display = 'block';
                    // Show all nested subcategories
                    const nestedSubcats = subcat.querySelectorAll('.lrob-carte-subcategory');
                    nestedSubcats.forEach(nested => nested.style.display = 'block');
                });
            } else if (level2CategoryId) {
                // Level 2 filter: show parent and only this specific level 2 subcategory
                allLevel1Subcategories.forEach(subcat => {
                    const subcatId = subcat.getAttribute('data-subcategory-id');

                    if (subcatId === level1CategoryId) {
                        // This is the parent - show it
                        subcat.style.display = 'block';

                        // Hide all its children except the selected one
                        const childSubcats = subcat.querySelectorAll(':scope > .lrob-carte-subcategories-container > .lrob-carte-subcategory');
                        childSubcats.forEach(child => {
                            const childId = child.getAttribute('data-subcategory-id');
                            child.style.display = childId === level2CategoryId ? 'block' : 'none';
                        });
                    } else {
                        // Hide other level 1 categories
                        subcat.style.display = 'none';
                    }
                });
            } else if (level1CategoryId) {
                // Level 1 filter: show only this category and all its children
                allLevel1Subcategories.forEach(subcat => {
                    const subcatId = subcat.getAttribute('data-subcategory-id');

                    if (subcatId === level1CategoryId) {
                        subcat.style.display = 'block';
                        // Show all children
                        const childSubcats = subcat.querySelectorAll('.lrob-carte-subcategory');
                        childSubcats.forEach(child => child.style.display = 'block');
                    } else {
                        subcat.style.display = 'none';
                    }
                });
            }
        }

        function resetCategoryFilters(rootCategory) {
            // Deselect all badges
            const allBadges = rootCategory.querySelectorAll('.lrob-subcategory-badge');
            allBadges.forEach(b => b.classList.remove('active'));

            // Hide all level 2 filters
            const level2Filters = rootCategory.querySelectorAll('.lrob-level-2-filters');
            level2Filters.forEach(f => f.style.display = 'none');

            // Show all subcategories
            filterSubcategories(rootCategory, null, null);
        }
    });
});
