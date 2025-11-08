document.addEventListener('DOMContentLoaded', function() {
    const carteWrappers = document.querySelectorAll('[data-carte-wrapper]');

    carteWrappers.forEach(wrapper => {
        const navItems = wrapper.querySelectorAll('.lrob-carte-nav-item');
        const rootCategories = wrapper.querySelectorAll('.lrob-carte-root-category');

        if (navItems.length > 0 && rootCategories.length > 1) {
            rootCategories.forEach((cat, index) => {
                if (index !== 0) cat.style.display = 'none';
            });

            navItems[0].classList.add('active');

            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const categoryId = this.dataset.category?.replace(/[^0-9]/g, '');
                    if (!categoryId) return;

                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');

                    rootCategories.forEach(cat => {
                        if (cat.dataset.categoryId === categoryId) {
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

        const subcategoryBadges = wrapper.querySelectorAll('.lrob-subcategory-badge');

        subcategoryBadges.forEach(badge => {
            badge.addEventListener('click', function(e) {
                e.preventDefault();

                const subcategoryId = this.dataset.subcategoryId?.replace(/[^0-9]/g, '');
                const parentId = this.dataset.parentId?.replace(/[^0-9]/g, '');
                const filterLevel = parseInt(this.dataset.filterLevel, 10);
                const isActive = this.classList.contains('active');

                const rootCategory = this.closest('.lrob-carte-root-category');
                if (!rootCategory) return;

                const rootCategoryId = rootCategory.dataset.categoryId;

                if (filterLevel === 1) {
                    handleLevel1BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive);
                } else if (filterLevel === 2) {
                    handleLevel2BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive);
                }
            });
        });

        function handleLevel1BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive) {
            const level1Badges = rootCategory.querySelectorAll('.lrob-subcategory-badge[data-filter-level="1"]');
            const level2Filters = rootCategory.querySelectorAll('.lrob-level-2-filters');

            if (isActive) {
                level1Badges.forEach(b => b.classList.remove('active'));
                level2Filters.forEach(f => f.style.display = 'none');
                filterSubcategories(rootCategory, null, null);
            } else {
                level1Badges.forEach(b => b.classList.remove('active'));

                const clickedBadge = rootCategory.querySelector(`.lrob-subcategory-badge[data-subcategory-id="${subcategoryId}"][data-filter-level="1"]`);
                if (clickedBadge) clickedBadge.classList.add('active');

                level2Filters.forEach(f => {
                    f.style.display = 'none';
                    f.querySelectorAll('.lrob-subcategory-badge').forEach(b => b.classList.remove('active'));
                });

                const level2FilterContainer = rootCategory.querySelector(`.lrob-level-2-filters[data-parent-id="${subcategoryId}"]`);
                if (level2FilterContainer) level2FilterContainer.style.display = 'flex';

                filterSubcategories(rootCategory, subcategoryId, null);
            }
        }

        function handleLevel2BadgeClick(rootCategory, rootCategoryId, subcategoryId, parentId, isActive) {
            const level2Container = rootCategory.querySelector(`.lrob-level-2-filters[data-parent-id="${parentId}"]`);
            if (!level2Container) return;

            const level2Badges = level2Container.querySelectorAll('.lrob-subcategory-badge[data-filter-level="2"]');

            if (isActive) {
                level2Badges.forEach(b => b.classList.remove('active'));
                filterSubcategories(rootCategory, parentId, null);
            } else {
                level2Badges.forEach(b => b.classList.remove('active'));
                const clickedBadge = level2Container.querySelector(`.lrob-subcategory-badge[data-subcategory-id="${subcategoryId}"]`);
                if (clickedBadge) clickedBadge.classList.add('active');
                filterSubcategories(rootCategory, parentId, subcategoryId);
            }
        }

        function filterSubcategories(rootCategory, level1CategoryId, level2CategoryId) {
            const subcategoriesWrapper = rootCategory.querySelector('.lrob-carte-subcategories-wrapper');
            if (!subcategoriesWrapper) return;

            const allLevel1Subcategories = subcategoriesWrapper.querySelectorAll(':scope > .lrob-carte-subcategory');

            if (!level1CategoryId && !level2CategoryId) {
                allLevel1Subcategories.forEach(subcat => {
                    subcat.style.display = 'block';
                    subcat.querySelectorAll('.lrob-carte-subcategory').forEach(nested => nested.style.display = 'block');
                });
            } else if (level2CategoryId) {
                allLevel1Subcategories.forEach(subcat => {
                    const subcatId = subcat.dataset.subcategoryId;
                    if (subcatId === level1CategoryId) {
                        subcat.style.display = 'block';
                        subcat.querySelectorAll(':scope > .lrob-carte-subcategories-container > .lrob-carte-subcategory')
                            .forEach(child => child.style.display = (child.dataset.subcategoryId === level2CategoryId) ? 'block' : 'none');
                    } else {
                        subcat.style.display = 'none';
                    }
                });
            } else if (level1CategoryId) {
                allLevel1Subcategories.forEach(subcat => {
                    const subcatId = subcat.dataset.subcategoryId;
                    if (subcatId === level1CategoryId) {
                        subcat.style.display = 'block';
                        subcat.querySelectorAll('.lrob-carte-subcategory').forEach(child => child.style.display = 'block');
                    } else {
                        subcat.style.display = 'none';
                    }
                });
            }
        }

        function resetCategoryFilters(rootCategory) {
            rootCategory.querySelectorAll('.lrob-subcategory-badge').forEach(b => b.classList.remove('active'));
            rootCategory.querySelectorAll('.lrob-level-2-filters').forEach(f => f.style.display = 'none');
            filterSubcategories(rootCategory, null, null);
        }
    });
});
