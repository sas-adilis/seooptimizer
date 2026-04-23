/**
 * SEO Optimizer — Tabbed SEO Configuration panel for entity edit pages.
 *
 * Self-injects when SeoOptimizerConfig is available (set via hookBackOfficeHeader).
 * Finds the native PrestaShop SEO section, wraps it in a tabbed interface,
 * and adds custom SEO fields + audit panel in separate tabs.
 *
 * Tab 1 — SEO: Google preview + native PS fields (moved) + custom fields
 * Tab 2 — Audit: entity audit panel (moved or loaded)
 */
(function () {
    if (typeof SeoOptimizerConfig === 'undefined') {
        return;
    }

    var cfg = SeoOptimizerConfig;
    var i18n = cfg.i18n || {};

    // ──────────────────────────────────────────────
    // 1. Find the native SEO section
    // ──────────────────────────────────────────────

    /**
     * Locate the native PrestaShop SEO card/section by finding
     * a meta_title input and walking up to its container.
     */
    function findNativeSeoSection() {
        // Known meta_title selectors across PS versions and entity types
        var metaTitleSelectors = [
            // Products PS 1.7
            '#form_step1_meta_title_1', '#form_step5_meta_title_1',
            // Products PS 8/9
            '#product_seo_meta_title_1', '#product_seo_meta_title',
            // Categories
            '#category_meta_title_1', '#category_seo_meta_title_1',
            // CMS
            '#cms_page_meta_title_1', '#cms_page_seo_meta_title_1',
            // Manufacturers / Suppliers
            '#manufacturer_meta_title_1', '#supplier_meta_title_1',
            // Meta pages
            '#meta_meta_title_1',
        ];

        var metaInput = null;
        for (var i = 0; i < metaTitleSelectors.length; i++) {
            metaInput = document.querySelector(metaTitleSelectors[i]);
            if (metaInput) break;
        }

        // Fallback: any input with meta_title in its name
        if (!metaInput) {
            metaInput = document.querySelector('input[name*="meta_title"]');
        }

        if (!metaInput) return null;

        // Walk up to find the containing card/panel
        var card = metaInput.closest('.card, .panel, .form-wrapper, .translation-label-full, fieldset');

        // If we found a small wrapper, try going one more level up
        if (card) {
            var parent = card.parentElement;
            // If the parent is also a card-like container, prefer it
            if (parent && parent.matches('.card, .panel, .card-body, .form-wrapper')) {
                // Check if the parent card also contains meta_description
                if (parent.querySelector('textarea[name*="meta_description"], input[name*="meta_description"]')) {
                    card = parent.closest('.card, .panel') || parent;
                }
            }
        }

        return card;
    }

    // ──────────────────────────────────────────────
    // 2. Build the tabbed panel
    // ──────────────────────────────────────────────

    function buildTabbedPanel() {
        var wrapper = document.createElement('div');
        wrapper.id = 'seoo-sc';
        wrapper.className = 'seoo-sc';

        wrapper.innerHTML = ''
            // Header
            + '<div class="seoo-sc__header">'
            +   '<div class="seoo-sc__title-row">'
            +     '<img class="seoo-sc__logo" src="' + esc(cfg.logoUrl) + '" alt="SEO Optimizer">'
            +     '<h4 class="seoo-sc__title">' + esc(i18n.title || 'SEO Configuration') + '</h4>'
            +     '<span id="seoo-sc-status" class="seoo-sc__status seoo-sc__status--idle"></span>'
            +   '</div>'
            + '</div>'

            // Tabs nav
            + '<div class="seoo-sc__tabs">'
            +   '<button type="button" class="seoo-sc__tab seoo-sc__tab--active" data-tab="seo">'
            +     iconSeo() + ' SEO'
            +   '</button>'
            +   '<button type="button" class="seoo-sc__tab" data-tab="audit">'
            +     iconAudit() + ' Audit'
            +   '</button>'
            + '</div>'

            // Tab panes
            + '<div class="seoo-sc__panes">'

            // ── Pane SEO ──
            +   '<div class="seoo-sc__pane seoo-sc__pane--active" id="seoo-pane-seo">'

            // Google preview
            +     '<div class="seoo-sc__section seoo-sc__section--preview">'
            +       '<div class="seoo-sc__section-label">'
            +         '<span class="seoo-sc__section-icon">' + iconSearch() + '</span>'
            +         esc(i18n.googlePreview || 'Google Preview')
            +       '</div>'
            +       '<div class="seoo-sc__serp" id="seoo-sc-serp">'
            +         '<cite class="seoo-sc__serp-url" id="seoo-sc-serp-url"></cite>'
            +         '<h3 class="seoo-sc__serp-title" id="seoo-sc-serp-title"></h3>'
            +         '<p class="seoo-sc__serp-desc" id="seoo-sc-serp-desc"></p>'
            +       '</div>'
            +     '</div>'

            // Placeholder for native fields
            +     '<div id="seoo-native-fields"></div>'

            // Separator
            +     '<div class="seoo-sc__separator"></div>'

            // Custom fields — Keywords (per language)
            +     '<div class="seoo-sc__section">'
            +       '<label class="seoo-sc__label">'
            +         '<span class="seoo-sc__section-icon">' + iconKey() + '</span>'
            +         esc(i18n.focusKeywords || 'Focus Keywords')
            +       '</label>'
            +       buildLangField('keywords', 'text', i18n.keywordsPlaceholder || '')
            +       '<p class="seoo-sc__help">' + esc(i18n.keywordsHelp || '') + '</p>'
            +     '</div>'

            // Canonical URL (per language)
            +     '<div class="seoo-sc__section">'
            +       '<label class="seoo-sc__label">'
            +         '<span class="seoo-sc__section-icon">' + iconLink() + '</span>'
            +         esc(i18n.canonicalUrl || 'Canonical URL')
            +       '</label>'
            +       buildLangField('canonical_url', 'url', i18n.canonicalPlaceholder || '')
            +       '<p class="seoo-sc__help">' + esc(i18n.canonicalHelp || '') + '</p>'
            +     '</div>'

            +     '<div class="seoo-sc__section seoo-sc__section--row">'
            +       '<div class="seoo-sc__field-group">'
            +         '<label class="seoo-sc__label" for="seoo_noindex">'
            +           '<span class="seoo-sc__section-icon">' + iconIndex() + '</span>'
            +           esc(i18n.indexation || 'Indexation')
            +         '</label>'
            +         '<select id="seoo_noindex" class="seoo-sc__select">'
            +           '<option value="0">' + esc(i18n.defaultIndex || 'Default (index)') + '</option>'
            +           '<option value="1">' + esc(i18n.noindex || 'Noindex') + '</option>'
            +         '</select>'
            +       '</div>'
            +       '<div class="seoo-sc__field-group">'
            +         '<label class="seoo-sc__label" for="seoo_nofollow">'
            +           '<span class="seoo-sc__section-icon">' + iconFollow() + '</span>'
            +           esc(i18n.linkFollowing || 'Link following')
            +         '</label>'
            +         '<select id="seoo_nofollow" class="seoo-sc__select">'
            +           '<option value="0">' + esc(i18n.defaultFollow || 'Default (follow)') + '</option>'
            +           '<option value="1">' + esc(i18n.nofollow || 'Nofollow') + '</option>'
            +         '</select>'
            +       '</div>'
            +     '</div>'

            // Save
            +     '<div class="seoo-sc__actions">'
            +       '<button type="button" id="seoo-sc-save" class="btn btn-primary btn-sm">'
            +         '<i class="icon-save"></i> ' + esc(i18n.save || 'Save SEO settings')
            +       '</button>'
            +     '</div>'

            +   '</div>' // end pane SEO

            // ── Pane Audit ──
            +   '<div class="seoo-sc__pane" id="seoo-pane-audit">'
            +     '<div id="seoo-audit-placeholder"></div>'
            +   '</div>'

            + '</div>'; // end panes

        return wrapper;
    }

    // ──────────────────────────────────────────────
    // 3. Inject and wire up
    // ──────────────────────────────────────────────

    function inject() {
        // Already injected?
        if (document.getElementById('seoo-sc')) return;

        var panel = buildTabbedPanel();
        var nativeSection = findNativeSeoSection();

        if (nativeSection) {
            // Insert our panel right before the native SEO section
            nativeSection.parentNode.insertBefore(panel, nativeSection);

            // Move native section into our "SEO" tab
            var nativeSlot = document.getElementById('seoo-native-fields');
            if (nativeSlot) {
                // Strip the card wrapper styling — keep fields, lose the chrome
                nativeSection.classList.add('seoo-sc__native');
                nativeSlot.appendChild(nativeSection);
            }
        } else {
            // No native section found — just append to the form or page
            var form = document.querySelector(
                'form[name="category"], form[name="root_category"],'
                + 'form[name="cms_page"], form[name="manufacturer"],'
                + 'form[name="supplier"], form.product-page, #product_form'
            );
            if (form) {
                form.appendChild(panel);
            } else {
                var main = document.querySelector('#content, #main, .content-div');
                if (main) main.appendChild(panel);
            }
        }

        // Move existing audit panel into the Audit tab
        var auditPanel = document.getElementById('seoo-ea');
        var auditSlot = document.getElementById('seoo-audit-placeholder');
        if (auditPanel && auditSlot) {
            auditSlot.appendChild(auditPanel);
        }

        // Wire tabs
        var tabs = panel.querySelectorAll('.seoo-sc__tab');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var target = this.getAttribute('data-tab');
                // Toggle tab active states
                tabs.forEach(function (t) { t.classList.remove('seoo-sc__tab--active'); });
                this.classList.add('seoo-sc__tab--active');
                // Toggle pane visibility
                var panes = panel.querySelectorAll('.seoo-sc__pane');
                panes.forEach(function (p) { p.classList.remove('seoo-sc__pane--active'); });
                var pane = document.getElementById('seoo-pane-' + target);
                if (pane) pane.classList.add('seoo-sc__pane--active');
            });
        });

        // Wire language tabs
        bindLangTabs(panel);

        // Save button
        var saveBtn = document.getElementById('seoo-sc-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                saveConfig();
            });
        }

        // Auto-save on main form submit
        var form = panel.closest('form');
        if (form) {
            form.addEventListener('submit', function () { saveConfig(); });
        }
    }

    // ──────────────────────────────────────────────
    // 4. Google Preview (reads native PS fields live)
    // ──────────────────────────────────────────────

    function updateSerpPreview() {
        var title = readField('title');
        var desc = readField('description');

        var elTitle = document.getElementById('seoo-sc-serp-title');
        var elDesc = document.getElementById('seoo-sc-serp-desc');
        var elUrl = document.getElementById('seoo-sc-serp-url');

        if (elTitle) {
            elTitle.textContent = title
                ? (title.length > 60 ? title.substring(0, 57) + '\u2026' : title)
                : (i18n.noTitle || '(no title)');
            elTitle.style.color = title ? '' : '#9ca3af';
        }
        if (elDesc) {
            elDesc.textContent = desc
                ? (desc.length > 160 ? desc.substring(0, 157) + '\u2026' : desc)
                : (i18n.noDescription || '(no description)');
            elDesc.style.color = desc ? '' : '#9ca3af';
        }
        if (elUrl) {
            try {
                var p = new URL(cfg.entityUrl);
                var parts = p.pathname.split('/').filter(function (s) { return s; });
                elUrl.textContent = p.host + (parts.length ? ' \u203A ' + parts.join(' \u203A ') : '');
            } catch (e) {
                elUrl.textContent = cfg.entityUrl;
            }
        }
    }

    function readField(type) {
        // type: 'title' or 'description'
        var tag = type === 'description' ? 'textarea' : 'input';
        var key = type === 'description' ? 'meta_description' : 'meta_title';

        // Try specific selectors first
        var prefixes = [
            // Products
            '#form_step1_', '#form_step5_', '#product_seo_',
            // Categories
            '#category_', '#category_seo_',
            // CMS
            '#cms_page_', '#cms_page_seo_',
            // Manufacturers / Suppliers
            '#manufacturer_', '#supplier_',
            // Meta
            '#meta_',
        ];

        for (var i = 0; i < prefixes.length; i++) {
            var el = document.querySelector(prefixes[i] + key + '_1');
            if (el && el.value) return el.value;
        }

        // Fallback: any matching field
        var el2 = document.querySelector(tag + '[name*="' + key + '"]');
        if (el2 && el2.value) return el2.value;

        // Visible translation field
        var el3 = document.querySelector('.translation-field:not(.d-none) ' + tag + '[name*="' + key + '"]');
        if (el3 && el3.value) return el3.value;

        return '';
    }

    function bindLivePreview() {
        // Watch all meta fields for changes
        var fields = document.querySelectorAll(
            'input[name*="meta_title"], textarea[name*="meta_description"],'
            + 'input[id*="meta_title"], textarea[id*="meta_description"]'
        );
        fields.forEach(function (f) {
            f.addEventListener('input', updateSerpPreview);
            f.addEventListener('change', updateSerpPreview);
        });

        // Language tab switches
        document.addEventListener('click', function (e) {
            if (e.target.closest('.translation-label, .nav-link, [data-locale], .translationsLocales, .js-locale-item')) {
                setTimeout(updateSerpPreview, 150);
            }
        });
    }

    // ──────────────────────────────────────────────
    // 5. Load / Save config
    // ──────────────────────────────────────────────

    function loadConfig() {
        var sep = cfg.ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
        var hasLangs = languages.length > 1;
        var url = cfg.ajaxUrl + sep + 'ajax=1&action=getSeoConfig'
            + '&entity_type=' + encodeURIComponent(cfg.entityType)
            + '&id_entity=' + cfg.idEntity
            + (hasLangs ? '&all_langs=1' : '');

        xhr('GET', url, null, function (data) {
            if (!data) return;

            if (hasLangs) {
                // data is keyed by id_lang
                var firstLangData = null;
                for (var i = 0; i < languages.length; i++) {
                    var lid = languages[i].id_lang;
                    var langData = data[lid] || data[String(lid)] || {};
                    if (!firstLangData) firstLangData = langData;
                    setVal('seoo_keywords_' + lid, langData.keywords || '');
                    setVal('seoo_canonical_url_' + lid, langData.canonical_url || '');
                }
                // noindex/nofollow are shared across languages
                if (firstLangData) {
                    setVal('seoo_noindex', String(firstLangData.noindex || 0));
                    setVal('seoo_nofollow', String(firstLangData.nofollow || 0));
                }
            } else {
                setVal('seoo_keywords', data.keywords || '');
                setVal('seoo_canonical_url', data.canonical_url || '');
                setVal('seoo_noindex', String(data.noindex || 0));
                setVal('seoo_nofollow', String(data.nofollow || 0));
            }
        });
    }

    function saveConfig(cb) {
        showStatus('saving');
        var btn = document.getElementById('seoo-sc-save');
        if (btn) btn.disabled = true;

        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('action', 'saveSeoConfig');
        fd.append('entity_type', cfg.entityType);
        fd.append('id_entity', cfg.idEntity);
        fd.append('noindex', val('seoo_noindex'));
        fd.append('nofollow', val('seoo_nofollow'));

        if (languages.length > 1) {
            for (var i = 0; i < languages.length; i++) {
                var lid = languages[i].id_lang;
                fd.append('keywords_lang[' + lid + ']', val('seoo_keywords_' + lid));
                fd.append('canonical_url_lang[' + lid + ']', val('seoo_canonical_url_' + lid));
            }
        } else {
            fd.append('keywords', val('seoo_keywords'));
            fd.append('canonical_url', val('seoo_canonical_url'));
        }

        xhr('POST', cfg.ajaxUrl, fd, function (data, ok) {
            if (btn) btn.disabled = false;
            showStatus(ok ? 'saved' : 'error');
            if (cb) cb(ok);
        });
    }

    // ──────────────────────────────────────────────
    // 6. Language field builder
    // ──────────────────────────────────────────────

    var languages = cfg.languages || [];
    var defaultLang = cfg.defaultLang || (languages.length ? languages[0].id_lang : 0);

    /**
     * Build a per-language input field with tabs.
     * Falls back to a single input if only one language.
     */
    function buildLangField(fieldName, inputType, placeholder) {
        if (languages.length <= 1) {
            return '<input type="' + esc(inputType) + '" id="seoo_' + fieldName + '" class="seoo-sc__input" value="" placeholder="' + escAttr(placeholder) + '">';
        }

        var html = '<div class="seoo-sc__lang-tabs" data-field="' + esc(fieldName) + '">';
        for (var i = 0; i < languages.length; i++) {
            var lang = languages[i];
            var active = lang.id_lang === defaultLang ? ' seoo-sc__lang-tab--active' : '';
            html += '<button type="button" class="seoo-sc__lang-tab' + active + '" data-lang="' + lang.id_lang + '">'
                + esc(lang.iso_code) + '</button>';
        }
        html += '</div>';

        for (var j = 0; j < languages.length; j++) {
            var lang2 = languages[j];
            var hidden = lang2.id_lang !== defaultLang ? ' seoo-sc__lang-field--hidden' : '';
            html += '<div class="seoo-sc__lang-field' + hidden + '" data-lang-field="' + fieldName + '-' + lang2.id_lang + '">'
                + '<input type="' + esc(inputType) + '" id="seoo_' + fieldName + '_' + lang2.id_lang
                + '" class="seoo-sc__input" value="" placeholder="' + escAttr(placeholder) + '">'
                + '</div>';
        }

        return html;
    }

    /**
     * Wire language tab switching for the panel.
     */
    function bindLangTabs(panel) {
        var tabGroups = panel.querySelectorAll('.seoo-sc__lang-tabs');
        tabGroups.forEach(function (group) {
            var field = group.getAttribute('data-field');
            var tabs = group.querySelectorAll('.seoo-sc__lang-tab');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    var langId = this.getAttribute('data-lang');
                    // Toggle active tab
                    tabs.forEach(function (t) { t.classList.remove('seoo-sc__lang-tab--active'); });
                    this.classList.add('seoo-sc__lang-tab--active');
                    // Toggle field visibility
                    var fields = panel.querySelectorAll('[data-lang-field^="' + field + '-"]');
                    fields.forEach(function (f) {
                        if (f.getAttribute('data-lang-field') === field + '-' + langId) {
                            f.classList.remove('seoo-sc__lang-field--hidden');
                        } else {
                            f.classList.add('seoo-sc__lang-field--hidden');
                        }
                    });
                });
            });
        });
    }

    // ──────────────────────────────────────────────
    // 7. Helpers
    // ──────────────────────────────────────────────

    function xhr(method, url, body, cb) {
        var x = new XMLHttpRequest();
        x.open(method, url, true);
        x.onreadystatechange = function () {
            if (x.readyState !== 4) return;
            var ok = false, data = null;
            if (x.status === 200) {
                try {
                    var r = JSON.parse(x.responseText);
                    ok = r.status === 'success';
                    data = r.data || null;
                } catch (e) { /* noop */ }
            }
            cb(data, ok);
        };
        x.send(body);
    }

    function val(id) { var e = document.getElementById(id); return e ? e.value : ''; }

    function setVal(id, v) {
        var e = document.getElementById(id);
        if (!e) return;
        if (e.tagName === 'SELECT') {
            for (var i = 0; i < e.options.length; i++) {
                if (e.options[i].value === v) { e.selectedIndex = i; break; }
            }
        } else {
            e.value = v;
        }
    }

    function showStatus(type) {
        var s = document.getElementById('seoo-sc-status');
        if (!s) return;
        var map = {
            saving: 'seoo-sc__status--saving',
            saved: 'seoo-sc__status--saved',
            error: 'seoo-sc__status--error',
        };
        s.className = 'seoo-sc__status ' + (map[type] || 'seoo-sc__status--idle');
        s.textContent = type === 'saved' ? '\u2713' : type === 'error' ? '\u2717' : '';
        if (type === 'saved' || type === 'error') {
            setTimeout(function () { showStatus('idle'); }, 3000);
        }
    }

    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }
    function escAttr(s) { return esc(s).replace(/"/g, '&quot;'); }

    // ── SVG Icons ──
    function iconSeo() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M229.66,218.34l-50.06-50.06a88.21,88.21,0,1,0-11.32,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg>';
    }
    function iconAudit() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 256 256" fill="currentColor"><path d="M176,232a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h80A8,8,0,0,1,176,232Zm40-128a87.55,87.55,0,0,1-33.64,69.21A16.24,16.24,0,0,0,176,186v6a16,16,0,0,1-16,16H96a16,16,0,0,1-16-16v-6a16,16,0,0,0-6.23-12.66A87.59,87.59,0,0,1,40,104.49C39.74,56.83,78.26,17.14,125.88,16A88,88,0,0,1,216,104Zm-16,0a72,72,0,0,0-73.74-72c-39,.92-70.47,33.39-70.26,72.39a71.64,71.64,0,0,0,27.64,56.3A32,32,0,0,1,96,186v6h24V147.31L98.34,125.66a8,8,0,0,1,11.32-11.32L128,132.69l18.34-18.35a8,8,0,0,1,11.32,11.32L136,147.31V192h24v-6a32.12,32.12,0,0,1,12.47-25.4A71.65,71.65,0,0,0,200,104Z"/></svg>';
    }
    function iconSearch() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256" fill="currentColor"><path d="M168,112a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,112Zm-8,24H96a8,8,0,0,0,0,16h64a8,8,0,0,0,0-16Zm72-72V200a16,16,0,0,1-16,16H40a16,16,0,0,1-16-16V64A16,16,0,0,1,40,48H216A16,16,0,0,1,232,64Zm-16,0H40V200H216Z"/></svg>';
    }
    function iconKey() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256" fill="currentColor"><path d="M229.66,218.34l-50.06-50.06a88.21,88.21,0,1,0-11.32,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg>';
    }
    function iconLink() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256" fill="currentColor"><path d="M137.54,186.36a8,8,0,0,1,0,11.31l-9.94,10A56,56,0,0,1,48.38,128.4L72.5,104.28A56,56,0,0,1,149.31,102a8,8,0,1,1-10.64,12,40,40,0,0,0-54.85,1.63L59.7,139.72a40,40,0,0,0,56.58,56.58l9.94-9.94A8,8,0,0,1,137.54,186.36Zm70.08-138a56.06,56.06,0,0,0-79.22,0l-9.94,9.95a8,8,0,0,0,11.32,11.31l9.94-9.94a40,40,0,0,1,56.58,56.58L172.18,140.4A40,40,0,0,1,117.33,142,8,8,0,1,0,106.69,154a56,56,0,0,0,76.81-2.26l24.12-24.12A56.06,56.06,0,0,0,207.62,48.38Z"/></svg>';
    }
    function iconIndex() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256" fill="currentColor"><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Zm0,176H48V48H208V208Zm-32-80a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h80A8,8,0,0,1,176,128Zm0,32a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h80A8,8,0,0,1,176,160Zm0-64a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h80A8,8,0,0,1,176,96Z"/></svg>';
    }
    function iconFollow() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 256 256" fill="currentColor"><path d="M200,64V168a8,8,0,0,1-16,0V83.31L69.66,197.66a8,8,0,0,1-11.32-11.32L172.69,72H88a8,8,0,0,1,0-16H192A8,8,0,0,1,200,64Z"/></svg>';
    }

    // ──────────────────────────────────────────────
    // 7. Init
    // ──────────────────────────────────────────────

    function init() {
        inject();
        loadConfig();
        setTimeout(function () {
            updateSerpPreview();
            bindLivePreview();
        }, 200);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(init, 500); });
    } else {
        setTimeout(init, 500);
    }
})();
