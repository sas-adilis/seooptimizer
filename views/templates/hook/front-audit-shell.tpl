{* SEO Audit — Shell injected via displayBeforeBodyClosingTag *}
{* Actual audit content is fetched via AJAX from the pageaudit controller *}

{* -- Floating button (loading state) -- *}
<div id="seoo-fa-btn" class="seoo-fa-btn" onclick="document.getElementById('seoo-fa-panel').classList.toggle('seoo-fa-panel--open');this.classList.toggle('seoo-fa-btn--hidden');">
    <span id="seoo-fa-btn-grade" class="seoo-fa-btn__grade"><span class="seoo-fa-spinner"></span></span>
    <span id="seoo-fa-btn-score" class="seoo-fa-btn__score"></span>
</div>

{* -- Side Panel -- *}
<div id="seoo-fa-panel" class="seoo-fa-panel">
    <div class="seoo-fa-panel__header">
        <span class="seoo-fa-panel__title">SEO Audit</span>
        <button class="seoo-fa-panel__close" type="button" onclick="document.getElementById('seoo-fa-panel').classList.remove('seoo-fa-panel--open');document.getElementById('seoo-fa-btn').classList.remove('seoo-fa-btn--hidden');">&times;</button>
    </div>
    <div id="seoo-fa-panel-body" class="seoo-fa-panel__body">
        <div class="seoo-fa-loading">
            <div class="seoo-fa-spinner seoo-fa-spinner--large"></div>
            <p>{l s='Analyse SEO en cours...' mod='seooptimizer'}</p>
        </div>
    </div>
</div>
