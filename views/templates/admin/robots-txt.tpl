<div class="seoo-robots seoo-screen" id="seoo-robots">
        <div class="seoo-panel-intro">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-robots.png" alt="{l s='Robots.txt' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/file-text.svg"}</span>
                    {l s='Robots.txt' mod='seooptimizer'}
                </h3>
                <p class="seoo-panel-intro__desc">{l s='Edit the content of your robots.txt file. Choose a preset adapted to your shop, then customize it if needed.' mod='seooptimizer'}</p>
            </div>
            <div class="seoo-panel-intro__actions">
                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#seooRobotsUrlTesterModal">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/magnifying-glass.svg"}</span> {l s='Test URL' mod='seooptimizer'}
                </button>
                <a href="{$seoo_robots_form_action|escape:'htmlall':'UTF-8'}&submitFormRobotsTxtReset=1&token={$seoo_robots_token|escape:'htmlall':'UTF-8'}" class="btn btn-default" onclick="return confirm('{l s="This will regenerate the robots.txt using PrestaShop defaults. Continue?" mod="seooptimizer" js=1}');">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/arrow-counter-clockwise.svg"}</span> {l s='Reset to PrestaShop default' mod='seooptimizer'}
                </a>
                <a href="{$seoo_robots_live_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/arrow-square-out.svg"}</span> {l s='View live file' mod='seooptimizer'}
                </a>
            </div>
        </div>

        <div class="panel-body">
            {if isset($seoo_robots_success) && $seoo_robots_success}
                <div class="alert alert-success">{l s='The robots.txt file has been saved successfully.' mod='seooptimizer'}</div>
            {/if}
            {if isset($seoo_robots_error) && $seoo_robots_error}
                <div class="alert alert-danger">{$seoo_robots_error|escape:'htmlall':'UTF-8'}</div>
            {/if}

            <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">{l s='Choose a preset adapted to your shop, then customize it if needed.' mod='seooptimizer'}</p>

            <div class="seoo-robots__presets">
                {foreach $seoo_robots_presets as $preset_key => $preset}
                    <div class="seoo-robots__preset {if $preset_key == 'standard'}seoo-robots__preset--active{/if}"
                         data-preset="{$preset_key|escape:'htmlall':'UTF-8'}">
                        {if isset($preset.recommended) && $preset.recommended}
                            <span class="seoo-robots__preset-badge">{l s='Recommended' mod='seooptimizer'}</span>
                        {/if}
                        <div class="seoo-robots__preset-icon"><i class="{$preset.icon_class|escape:'htmlall':'UTF-8'}" style="font-size:28px;color:{$preset.icon_color|escape:'htmlall':'UTF-8'}"></i></div>
                        <div class="seoo-robots__preset-name">{$preset.name|escape:'htmlall':'UTF-8'}</div>
                        <div class="seoo-robots__preset-desc">{$preset.desc|escape:'htmlall':'UTF-8'}</div>
                    </div>
                {/foreach}
            </div>

            <form method="post" action="{$seoo_robots_form_action|escape:'htmlall':'UTF-8'}" id="seoo-robots-form">
                <input type="hidden" name="token" value="{$seoo_robots_token|escape:'htmlall':'UTF-8'}">

                <div class="seoo-robots__editor-toolbar">
                    <span class="seoo-robots__editor-label"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/pencil-simple.svg"}</span> {l s='robots.txt editor' mod='seooptimizer'}</span>
                    <div class="seoo-robots__editor-status">
                        <span class="seoo-robots__status-dot seoo-robots__status-dot--ok" id="seooRobotsStatusDot"></span>
                        <span id="seooRobotsStatusText">{l s='No errors detected' mod='seooptimizer'}</span>
                    </div>
                </div>
                <textarea name="SEOO_ROBOTS_TXT" id="seooRobotsEditor" class="seoo-robots__editor" spellcheck="false">{$seoo_robots_content|escape:'htmlall':'UTF-8'}</textarea>

                <div class="seoo-robots__save-bar">
                    <button type="submit" name="submitFormRobotsTxt" class="btn btn-default" style="background:#05808B;border-color:#05808B;color:#fff;">
                        <span class="seoo-icon">{include file="module:seooptimizer/views/icons/floppy-disk.svg"}</span> {l s='Save' mod='seooptimizer'}
                    </button>
                </div>

                <div class="seoo-robots__validation" id="seooRobotsValidation">
                    <div class="seoo-robots__validation-header seoo-robots__validation-header--ok" id="seooRobotsValidationHeader">
                    </div>
                    <div class="seoo-robots__validation-items" id="seooRobotsValidationItems">
                    </div>
                </div>

            </form>
        </div>

    {if $seoo_robots_history_html}
        <div class="seoo-panel-intro" style="border-top:1px solid #e8e8e8;">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-history.png" alt="{l s='History' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/clock.svg"}</span> {l s='History' mod='seooptimizer'}</h3>
                <p class="seoo-panel-intro__desc">{l s='Previous versions of your robots.txt file. You can restore any backup with one click.' mod='seooptimizer'}</p>
            </div>
        </div>
        {$seoo_robots_history_html nofilter}
    {/if}
</div>

{* ── URL Tester Modal ── *}
<div class="modal fade" id="seooRobotsUrlTesterModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/magnifying-glass.svg"}</span> {l s='URL Tester' mod='seooptimizer'}</h4>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">{l s='Check if a URL will be blocked or allowed by your current robots.txt rules.' mod='seooptimizer'}</p>
                <div class="seoo-robots__url-tester-row">
                    <input type="text" id="seooRobotsTestUrl" class="form-control" placeholder="/my-product.html" value="/recherche?q=chaussures" style="flex:1;">
                    <button type="button" class="btn btn-default" id="seooRobotsTestBtn">
                        <span class="seoo-icon">{include file="module:seooptimizer/views/icons/magnifying-glass.svg"}</span> {l s='Test' mod='seooptimizer'}
                    </button>
                </div>
                <div id="seooRobotsTestResult" class="seoo-robots__test-result" style="display:none;margin-top:12px;"></div>
            </div>
        </div>
    </div>
</div>
