document.addEventListener('DOMContentLoaded', function() {
    const carteWrappers = document.querySelectorAll('[data-carte-wrapper]');

    carteWrappers.forEach(wrapper => {
        const navItems = wrapper.querySelectorAll('.lrob-carte-nav-item');
        const rootCategories = wrapper.querySelectorAll('.lrob-carte-root-category');

        // Initialiser : tout afficher par défaut
        initializeAllCategories(wrapper);

        // Navigation principale
        if (navItems.length > 0 && rootCategories.length > 1) {
            rootCategories.forEach((cat, index) => {
                if (index !== 0) {
                    cat.style.display = 'none';
                }
            });
            
            navItems[0].classList.add('active');

            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const categoryId = this.getAttribute('data-category');

                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');

                    rootCategories.forEach(cat => {
                        if (cat.getAttribute('data-category-id') === categoryId) {
                            cat.style.display = 'block';
                            resetCategoryToShowAll(cat);
                        } else {
                            cat.style.display = 'none';
                        }
                    });

                    this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });
        }

        // Gestion des badges de sous-catégories avec multi-sélection
        const subcategoryBadges = wrapper.querySelectorAll('.lrob-subcategory-badge');
        
        subcategoryBadges.forEach(badge => {
            badge.addEventListener('click', function(e) {
                e.preventDefault();
                
                const subcategoryId = this.getAttribute('data-subcategory');
                const parentId = this.getAttribute('data-parent');
                
                const parentCategory = wrapper.querySelector(`.lrob-carte-category[data-category-id="${parentId}"]`);
                if (!parentCategory) return;
                
                // Toggle ce badge
                this.classList.toggle('active');

                // Récupérer tous les badges du même parent
                const filterContainer = this.closest('.lrob-subcategory-filters');
                const allBadges = filterContainer.querySelectorAll('.lrob-subcategory-badge');
                const activeBadges = Array.from(allBadges).filter(b => b.classList.contains('active'));

                // Trouver le conteneur de sous-catégories du même parent
                const subcategoriesContainer = parentCategory.querySelector(`.lrob-carte-subcategories-container[data-parent="${parentId}"]`);
                if (!subcategoriesContainer) return;

                // Récupérer toutes les sous-catégories directes
                const allSubcats = subcategoriesContainer.querySelectorAll(':scope > .lrob-carte-category');

                if (activeBadges.length === 0) {
                    // Aucun badge actif = tout afficher
                    allSubcats.forEach(subcat => {
                        subcat.style.display = 'block';
                    });
                } else {
                    // Filtrer : afficher uniquement les catégories des badges actifs
                    const activeIds = activeBadges.map(b => b.getAttribute('data-subcategory'));
                    
                    allSubcats.forEach(subcat => {
                        const subcatId = subcat.getAttribute('data-category-id');
                        subcat.style.display = activeIds.includes(subcatId) ? 'block' : 'none';
                    });
                }
            });
        });

        function initializeAllCategories(wrapper) {
            // S'assurer que tous les conteneurs et sous-catégories sont visibles au départ
            const allContainers = wrapper.querySelectorAll('.lrob-carte-subcategories-container');
            allContainers.forEach(container => {
                container.style.display = 'block';
                const subcats = container.querySelectorAll(':scope > .lrob-carte-category');
                subcats.forEach(s => s.style.display = 'block');
            });
        }

        function resetCategoryToShowAll(categoryElement) {
            // Désactiver tous les badges
            const badges = categoryElement.querySelectorAll('.lrob-subcategory-badge');
            badges.forEach(b => b.classList.remove('active'));
            
            // Afficher tous les conteneurs et sous-catégories
            const containers = categoryElement.querySelectorAll('.lrob-carte-subcategories-container');
            containers.forEach(c => {
                c.style.display = 'block';
                const subcats = c.querySelectorAll(':scope > .lrob-carte-category');
                subcats.forEach(s => s.style.display = 'block');
            });
        }
    });
});
