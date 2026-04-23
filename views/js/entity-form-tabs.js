/**
 * SeoOptimizerFormInjector — Injects the SEO block at the bottom of a BO entity form.
 *
 * Steps:
 *   1. Find the entity form
 *   2. Move the module SEO block (rendered in BO footer) into the form, before the submit bar
 *   3. Find native PS SEO fields (meta_title, meta_description, link_rewrite) and move them
 *      into #seoo-native-seo-fields inside our block
 *   4. Bind SERP preview to native fields
 *
 * @constructor
 * @param {Object} config
 */
window.SeoOptimizerFormInjector = function (config) {
    this.config = config;
    this.entityType = config.entityType;
    this.blockId = 'seoo-' + this.entityType + '-seo-tab';
    this.block = document.getElementById(this.blockId);
    this.form = this._findForm();

    console.log('[SEOO Injector] entityType:', this.entityType, '| block:', !!this.block, '| form:', !!this.form);

    if (this.block && this.form) {
        this._boot();
    } else {
        console.warn('[SEOO Injector] Missing block or form — aborting');
    }
};

// ─────────────────────────────────────────────────
// Boot
// ─────────────────────────────────────────────────

SeoOptimizerFormInjector.prototype._boot = function () {
    var self = this;
    setTimeout(function () { self._run(); }, 300);
};

SeoOptimizerFormInjector.prototype._run = function () {
    // 1. Find insert point: before the card-footer / submit bar
    var footer = this.form.querySelector('.card-footer, .form-action-bar, .fixed-bottom-actions');
    var cardBody = this.form.querySelector('.card-body');

    if (cardBody) {
        // Insert at the end of card-body
        cardBody.appendChild(this.block);
    } else if (footer) {
        // Insert before the footer
        footer.parentNode.insertBefore(this.block, footer);
    } else {
        // Fallback: append to form
        this.form.appendChild(this.block);
    }

    this.block.style.display = '';
    console.log('[SEOO Injector] Block injected into form');

    // 2. Move SERP preview into its slot
    //    Priority: native PS #serp-app (PS 8/9), fallback to our SerpPreviewType (.seoo-serp-preview)
    var serpSlot = document.getElementById('seoo-serp-preview-slot');
    if (serpSlot) {
        var serpEl = this.form.querySelector('#serp-app') || this.form.querySelector('.seoo-serp-preview');
        if (serpEl) {
            var serpFormGroup = serpEl.closest('.form-group') || serpEl.parentElement;
            serpSlot.appendChild(serpFormGroup);
            console.log('[SEOO Injector] SERP preview moved (' + (serpEl.id === 'serp-app' ? 'native' : 'module') + ')');
        }
    }

    // 3. Move native SEO fields into our block
    var nativeSlot = document.getElementById('seoo-native-seo-fields');
    if (nativeSlot) {
        var seoGroups = this._findNativeSeoGroups();
        console.log('[SEOO Injector] Native SEO groups found:', seoGroups.length);
        for (var i = 0; i < seoGroups.length; i++) {
            nativeSlot.appendChild(seoGroups[i]);
        }
    }

    // 4. Move module custom fields (keywords, canonical, noindex, nofollow) into our block
    var customSlot = document.getElementById('seoo-custom-seo-fields');
    if (customSlot) {
        var customGroups = this._findCustomSeoGroups();
        console.log('[SEOO Injector] Custom SEO groups found:', customGroups.length);
        for (var k = 0; k < customGroups.length; k++) {
            customSlot.appendChild(customGroups[k]);
        }
    }

    // 5. Init SERP preview
    this._initSerpPreview();

    // 6. Init tag hints (click to insert into meta fields)
    this._initTagHints();

    // 7. Init redirect toggle (show/hide URL field based on redirect type)
    this._initRedirectToggle();

    console.log('[SEOO Injector] Done');
};

// ─────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────

SeoOptimizerFormInjector.prototype._findForm = function () {
    var selectors = this.config.formSelectors || [];
    for (var i = 0; i < selectors.length; i++) {
        var el = document.querySelector(selectors[i]);
        if (el) {
            return el;
        }
    }
    return null;
};

/**
 * Find individual native PS SEO form-groups by locating known SEO inputs
 * and walking up to their .form-group parent.
 *
 * @return {HTMLElement[]}
 */
SeoOptimizerFormInjector.prototype._findNativeSeoGroups = function () {
    var seoSelectors = [
        'input[name*="meta_title"]',
        'textarea[name*="meta_description"]',
        'input[name*="link_rewrite"]',
        'input[name*="friendly_url"]'
    ];

    var groups = [];
    var seen = [];

    for (var i = 0; i < seoSelectors.length; i++) {
        var inputs = this.form.querySelectorAll(seoSelectors[i]);
        for (var j = 0; j < inputs.length; j++) {
            var group = inputs[j].closest('.form-group');
            if (group && seen.indexOf(group) === -1) {
                seen.push(group);
                groups.push(group);
                console.log('[SEOO Injector] SEO form-group found for:', inputs[j].name || inputs[j].id);
            }
        }
    }

    return groups;
};

/**
 * Find module custom SEO form-groups (seoo_keywords, seoo_canonical_url,
 * seoo_noindex, seoo_nofollow) added by the FormBuilderModifier,
 * and walk up to their .form-group parent.
 *
 * @return {HTMLElement[]}
 */
SeoOptimizerFormInjector.prototype._findCustomSeoGroups = function () {
    var customSelectors = [
        'input[name*="seoo_keywords"]',
        'input[name*="seoo_canonical_url"]',
        'select[name*="seoo_noindex"], input[name*="seoo_noindex"]',
        'select[name*="seoo_nofollow"], input[name*="seoo_nofollow"]',
        'select[name*="seoo_redirect_type"], input[name*="seoo_redirect_type"]',
        'input[name*="seoo_redirect_url"]'
    ];

    var groups = [];
    var seen = [];

    for (var i = 0; i < customSelectors.length; i++) {
        var inputs = this.form.querySelectorAll(customSelectors[i]);
        for (var j = 0; j < inputs.length; j++) {
            var group = inputs[j].closest('.form-group');
            if (group && seen.indexOf(group) === -1) {
                seen.push(group);
                groups.push(group);
                console.log('[SEOO Injector] Custom SEO form-group found for:', inputs[j].name || inputs[j].id);
            }
        }
    }

    return groups;
};

// ─────────────────────────────────────────────────
// Tag hints (injected below each meta field)
// ─────────────────────────────────────────────────

/**
 * Inject clickable tag badges below each meta_title and meta_description field.
 * Tags come from the global SeoOptimizerTags variable set by hookBackOfficeHeader.
 */
SeoOptimizerFormInjector.prototype._initTagHints = function () {
    if (typeof SeoOptimizerTags === 'undefined') return;

    var tags = SeoOptimizerTags;
    if (!tags || typeof tags !== 'object') return;

    // Find all meta_title inputs and meta_description textareas inside our block
    var fields = this.block.querySelectorAll(
        'input[name*="meta_title"], textarea[name*="meta_description"]'
    );

    for (var i = 0; i < fields.length; i++) {
        this._appendTagList(fields[i], tags);
    }
};

/**
 * Append a tag list after the given input/textarea.
 *
 * @param {HTMLElement} field
 * @param {Object} tags  { '{name}': 'Description', ... }
 */
SeoOptimizerFormInjector.prototype._appendTagList = function (field, tags) {
    var list = document.createElement('div');
    list.className = 'seoo-tag-hints__list';

    for (var tag in tags) {
        if (!tags.hasOwnProperty(tag)) continue;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'seoo-tag-hints__tag';
        btn.setAttribute('data-seoo-tag', tag);
        btn.title = tags[tag];
        btn.textContent = tag;
        list.appendChild(btn);
    }

    // Insert after the field (or after its parent wrapper if inside a translatable container)
    field.parentNode.insertBefore(list, field.nextSibling);

    // Click handler: insert tag at cursor position
    list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-seoo-tag]');
        if (!btn) return;
        e.preventDefault();

        var tagValue = btn.getAttribute('data-seoo-tag');
        var start = field.selectionStart || 0;
        var end = field.selectionEnd || 0;
        var val = field.value;
        field.value = val.substring(0, start) + tagValue + val.substring(end);
        field.selectionStart = field.selectionEnd = start + tagValue.length;
        field.focus();
        field.dispatchEvent(new Event('input', { bubbles: true }));
    });
};

// ─────────────────────────────────────────────────
// Redirect toggle
// ─────────────────────────────────────────────────

/**
 * Show the redirect URL field only when redirect type is 301 or 302.
 */
SeoOptimizerFormInjector.prototype._initRedirectToggle = function () {
    var typeSelect = this.block.querySelector('select[data-seoo-toggle-url]')
        || this.block.querySelector('select[name*="seoo_redirect_type"]');
    if (!typeSelect) return;

    var urlInput = this.block.querySelector('input[name*="seoo_redirect_url"]');
    if (!urlInput) return;

    var urlGroup = urlInput.closest('.form-group');
    if (!urlGroup) return;

    function toggle() {
        var val = typeSelect.value;
        urlGroup.style.display = (val === '301' || val === '302') ? '' : 'none';
    }

    typeSelect.addEventListener('change', toggle);
    toggle();
};

// ─────────────────────────────────────────────────
// SERP Preview (fallback when native PS Vue component is absent)
// ─────────────────────────────────────────────────

SeoOptimizerFormInjector.prototype._initSerpPreview = function () {
    // If native PS #serp-app is present, Vue.js handles everything — skip
    if (document.getElementById('serp-app')) {
        console.log('[SEOO Injector] Native SERP preview detected — skipping module fallback');
        return;
    }

    // Module fallback: our #seoo-serp element
    if (!document.getElementById('seoo-serp')) {
        return;
    }

    var self = this;
    this._updateSerpPreview();

    // Watch meta fields + name/description for changes
    var fields = this.form.querySelectorAll(
        'input[name*="meta_title"], textarea[name*="meta_description"],' +
        'input[name*="[name]"], textarea[name*="[description]"],' +
        'input[name*="link_rewrite"]'
    );
    for (var i = 0; i < fields.length; i++) {
        fields[i].addEventListener('input', function () { self._updateSerpPreview(); });
        fields[i].addEventListener('change', function () { self._updateSerpPreview(); });
    }

    // Language tab switches
    document.addEventListener('click', function (e) {
        if (e.target.closest('.translation-label, .nav-link, [data-locale], .translationsLocales, .js-locale-item')) {
            setTimeout(function () { self._updateSerpPreview(); }, 200);
        }
    });
};

SeoOptimizerFormInjector.prototype._updateSerpPreview = function () {
    // Title: meta_title with fallback to entity name
    var title = this._readVisibleField('meta_title', 'input')
        || this._readVisibleField('[name]', 'input');

    // Description: meta_description with fallback to entity description
    var desc = this._readVisibleField('meta_description', 'textarea')
        || this._readVisibleField('[description]', 'textarea');

    // URL: link_rewrite
    var rewrite = this._readVisibleField('link_rewrite', 'input') || '';

    var elTitle = document.getElementById('seoo-sc-serp-title');
    var elDesc = document.getElementById('seoo-sc-serp-desc');
    var elBaseUrl = document.getElementById('seoo-sc-serp-baseurl');
    var elPath = document.getElementById('seoo-sc-serp-path');

    if (elTitle) {
        elTitle.textContent = title
            ? (title.length > 70 ? title.substring(0, 70) + '...' : title)
            : '';
    }
    if (elDesc) {
        // Strip HTML from description
        var plainDesc = desc;
        if (desc && desc.indexOf('<') !== -1) {
            var tmp = document.createElement('div');
            tmp.innerHTML = desc;
            plainDesc = tmp.textContent || '';
        }
        elDesc.textContent = plainDesc
            ? (plainDesc.length > 150 ? plainDesc.substring(0, 150) + '...' : plainDesc)
            : '';
    }
    if (elBaseUrl && elPath) {
        var entityUrl = document.getElementById('seoo-serp')
            ? document.getElementById('seoo-serp').getAttribute('data-entity-url') || ''
            : '';
        try {
            var p = new URL(entityUrl);
            elBaseUrl.textContent = p.protocol + '//' + p.hostname;
            if (rewrite) {
                var path = decodeURI(p.pathname).replace(/[^/]*$/, '') + rewrite;
                elPath.textContent = path.replace(/\//g, ' \u203A ');
            } else {
                elPath.textContent = decodeURI(p.pathname).replace(/\//g, ' \u203A ');
            }
        } catch (e) {
            elBaseUrl.textContent = entityUrl;
            elPath.textContent = '';
        }
    }
};

/**
 * Read the value of the currently visible translation field matching the given key.
 *
 * @param {string} nameKey  Part of the field name to match (e.g. 'meta_title', '[name]')
 * @param {string} tag      'input' or 'textarea'
 * @return {string}
 */
SeoOptimizerFormInjector.prototype._readVisibleField = function (nameKey, tag) {
    // Visible translation field (PS multi-lang)
    var visible = this.form.querySelector(
        '.translation-field:not(.d-none) ' + tag + '[name*="' + nameKey + '"]'
    );
    if (visible && visible.value) {
        return visible.value;
    }

    // Specific selectors from config
    var selectors = [];
    if (nameKey === 'meta_title') selectors = this.config.metaTitleSelectors || [];
    if (nameKey === 'meta_description') selectors = this.config.metaDescSelectors || [];
    for (var i = 0; i < selectors.length; i++) {
        var el = document.querySelector(selectors[i]);
        if (el && el.value) return el.value;
    }

    // Fallback: any matching field
    var any = this.form.querySelector(tag + '[name*="' + nameKey + '"]');
    return (any && any.value) ? any.value : '';
};
