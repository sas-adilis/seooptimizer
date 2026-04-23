/**
 * SEO Optimizer — Category form SEO injection.
 *
 * Moves the SEO block (rendered in BO footer) into the category form
 * and relocates native PS SEO fields into it.
 */
(function () {
    'use strict';

    console.log('[SEOO category] Script loaded');

    function init() {
        console.log('[SEOO category] DOM ready — init()');

        if (!document.getElementById('seoo-category-seo-tab')) {
            console.warn('[SEOO category] #seoo-category-seo-tab not found — aborting');
            return;
        }

        console.log('[SEOO category] Initializing SeoOptimizerFormInjector');
        new SeoOptimizerFormInjector({
            entityType: 'category',

            formSelectors: [
                'form[name="category"]',
                'form[name="root_category"]'
            ],

            metaTitleSelectors: [
                '#category_meta_title_1',
                '#category_seo_meta_title_1'
            ],

            metaDescSelectors: [
                '#category_meta_description_1',
                '#category_seo_meta_description_1'
            ]
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
