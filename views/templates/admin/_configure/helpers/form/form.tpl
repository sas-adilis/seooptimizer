{extends file="helpers/form/form.tpl"}

{block name="legend"}
    {if isset($field.visual) && $field.visual}
        <div class="seoo-panel-intro">
            <div class="seoo-panel-intro__visual">
                <img src="{$field.visual|escape:'htmlall':'UTF-8'}" alt="{$field.title|escape:'htmlall':'UTF-8'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    {if isset($field.icon)}<i class="{$field.icon}"></i>{/if}
                    {$field.title|escape:'htmlall':'UTF-8'}
                </h3>
                {if isset($field.description) && $field.description}
                    <p class="seoo-panel-intro__desc">{$field.description|escape:'htmlall':'UTF-8'}</p>
                {/if}
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="field"}
    {if $input.type == 'report'}
        <div class="seoo-report {if isset($input.show_fixed) && $input.show_fixed}seoo-report--with-fixed{/if}" id="{$input.name|escape:'htmlall':'UTF-8'}_report">
            <div class="seoo-report__kpis">
                {assign var="total_entities" value=0}
                {assign var="done_entities" value=0}
                {assign var="total_errors" value=0}
                {assign var="total_analyzed" value=0}
                {foreach $input.definitions as $definition}
                    {assign var="total_entities" value=$total_entities+1}
                    {if $definition->getProgressPercentage()|floatval == 100}
                        {assign var="done_entities" value=$done_entities+1}
                    {/if}
                    {assign var="total_errors" value=$total_errors+$definition->getResultsCount()}
                    {assign var="total_analyzed" value=$total_analyzed+$definition->getCount()}
                {/foreach}
                <div class="seoo-report__kpi">
                    <span class="seoo-report__kpi-label">{l s='Scanned entities' mod='seooptimizer'}</span>
                    <span class="seoo-report__kpi-value" data-kpi="entities">{$done_entities|escape:'htmlall':'UTF-8'} / {$total_entities|escape:'htmlall':'UTF-8'}</span>
                </div>
                <div class="seoo-report__kpi">
                    <span class="seoo-report__kpi-label">{l s='Elements analyzed' mod='seooptimizer'}</span>
                    <span class="seoo-report__kpi-value" data-kpi="analyzed">{$total_analyzed|escape:'htmlall':'UTF-8'}</span>
                </div>
                <div class="seoo-report__kpi {if $total_errors > 0}seoo-report__kpi--danger{/if}">
                    <span class="seoo-report__kpi-label">{l s='Errors detected' mod='seooptimizer'}</span>
                    <span class="seoo-report__kpi-value" data-kpi="errors">{$total_errors|escape:'htmlall':'UTF-8'}</span>
                </div>
            </div>
            <div class="seoo-report__table">
                <div class="seoo-report__thead">
                    <div class="seoo-report__th seoo-report__th--entity">{l s='Entity' mod='seooptimizer'}</div>
                    <div class="seoo-report__th seoo-report__th--progress">{l s='Progression' mod='seooptimizer'}</div>
                    <div class="seoo-report__th seoo-report__th--result">{l s='Result' mod='seooptimizer'}</div>
                    {if isset($input.show_fixed) && $input.show_fixed}
                        <div class="seoo-report__th seoo-report__th--fixed">{l s='Fixed' mod='seooptimizer'}</div>
                    {/if}
                </div>
                {foreach $input.definitions as $definition}
                    <div class="seoo-report__row" id="{$input.name|escape:'htmlall':'UTF-8'}_form_{$definition->getKey()|escape:'htmlall':'UTF-8'}">
                        <div class="seoo-report__cell seoo-report__cell--entity">
                            <span class="seoo-report__icon"><i class="{$definition->getIcon()|escape:'htmlall':'UTF-8'}"></i></span>
                            <span class="seoo-report__entity-info">
                                <strong class="seoo-report__entity-name">{$definition->getTitle()|escape:'htmlall':'UTF-8'}</strong>
                                <span class="seoo-report__entity-count">{$definition->getCount()|escape:'htmlall':'UTF-8'} {l s='elements' mod='seooptimizer'}</span>
                            </span>
                        </div>
                        <div class="seoo-report__cell seoo-report__cell--progress">
                            <div class="seoo-report__bar-wrap">
                                <div class="progress report__progress-percentage">
                                    <div class="progress-bar {if $definition->getProgressPercentage()|floatval == 100}bg-success{elseif $definition->getProgressPercentage()|floatval > 0}bg-processing{/if}" role="progressbar" aria-valuenow="{$definition->getProgressPercentage()|floatval}" aria-valuemin="0" aria-valuemax="100" style="width: {$definition->getProgressPercentage()|floatval}%"></div>
                                </div>
                            </div>
                            <div class="seoo-report__status-line">
                                <span class="seoo-report__status-label report__progress-value">
                                    {if $definition->getProgressPercentage()|floatval == 100}
                                        {l s='Done' mod='seooptimizer'}
                                    {elseif $definition->getProgressPercentage()|floatval > 0}
                                        {l s='In progress' mod='seooptimizer'}
                                    {else}
                                        {l s='Waiting' mod='seooptimizer'}
                                    {/if}
                                </span>
                                <span class="seoo-report__progress-value report__progress-value">{$definition->getProgress()|escape:'htmlall':'UTF-8'}</span>
                            </div>
                        </div>
                        <div class="seoo-report__cell seoo-report__cell--result report__result">
                            <span class="seoo-report__badge {if $definition->getResultsCount() > 0}seoo-report__badge--danger{else}seoo-report__badge--success{/if}">
                                {$definition->getResultsCount()|escape:'htmlall':'UTF-8'}
                            </span>
                        </div>
                        {if isset($input.show_fixed) && $input.show_fixed}
                            <div class="seoo-report__cell seoo-report__cell--fixed report__fixed">
                                {$definition->getFixedCount()|escape:'htmlall':'UTF-8'}
                            </div>
                        {/if}
                    </div>
                {/foreach}
            </div>
        </div>
    {elseif $input.type == 'priorities_frequencies'}
        <div class="col-lg-8">
            <div class="form-group">
                <div class="col-md-2"></div>
                <div class="col-md-3 text-center">
                    <strong class="label-tooltip" data-toggle="tooltip" title="{l s='The priority field indicates the importance of a page relative to other pages on the same site. This value ranges from 0.0 (lowest priority) to 1.0 (highest priority)' mod='seooptimizer'}">
                        {l s='Priority' mod='seooptimizer'}
                    </strong>
                </div>
                <div class="col-md-3 text-center">
                    <strong class="label-tooltip" data-toggle="tooltip" title="{l s='The change frequency field provides a general indication of how often a page is likely to change. This information serves as a hint to search engines about how often they might need to revisit a page, although it is not strictly enforced' mod='seooptimizer'}">{l s='Frequency' mod='seooptimizer'}</strong>
                </div>
            </div>
            {foreach $input.options as $option}
                <div class="form-group">
                    <div class="col-md-2">
                        <strong>{$option.label|escape:'htmlall':'UTF-8'}</strong>
                    </div>
                    <div class="col-md-3">
                        <select name="{$option.name|escape:'htmlall':'UTF-8'}_PRIORITY">
                            <option value="1.0" {if $option.priority =='1.0'}selected="selected"{/if}>{l s='1.0 (critical)' mod='seooptimizer'}</option>
                            <option value="0.9" {if $option.priority =='0.9'}selected="selected"{/if}>{l s='0.9 (very important)' mod='seooptimizer'}</option>
                            <option value="0.8" {if $option.priority =='0.8'}selected="selected"{/if}>{l s='0.8' mod='seooptimizer'}</option>
                            <option value="0.7" {if $option.priority =='0.7'}selected="selected"{/if}>{l s='0.7 (important)' mod='seooptimizer'}</option>
                            <option value="0.6" {if $option.priority =='0.6'}selected="selected"{/if}>{l s='0.6' mod='seooptimizer'}</option>
                            <option value="0.5" {if $option.priority =='0.5'}selected="selected"{/if}>{l s='0.5 (medium)' mod='seooptimizer'}</option>
                            <option value="0.4" {if $option.priority =='0.4'}selected="selected"{/if}>{l s='0.4' mod='seooptimizer'}</option>
                            <option value="0.3" {if $option.priority =='0.3'}selected="selected"{/if}>{l s='0.3 (low)' mod='seooptimizer'}</option>
                            <option value="0.2" {if $option.priority =='0.2'}selected="selected"{/if}>{l s='0.2 (very low)' mod='seooptimizer'}</option>
                            <option value="0.1" {if $option.priority =='0.1'}selected="selected"{/if}>{l s='0.1' mod='seooptimizer'}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="{$option.name|escape:'htmlall':'UTF-8'}_FREQUENCY" class="" id="SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY">
                            <option value="always" {if $option.frequency =='always'}selected="selected"{/if}>{l s='Always' mod='seooptimizer'}</option>
                            <option value="hourly" {if $option.frequency =='hourly'}selected="selected"{/if}>{l s='Hourly' mod='seooptimizer'}</option>
                            <option value="daily" {if $option.frequency =='daily'}selected="selected"{/if}>{l s='Daily' mod='seooptimizer'}</option>
                            <option value="weekly" {if $option.frequency =='weekly'}selected="selected"{/if}>{l s='Weekly' mod='seooptimizer'}</option>
                            <option value="monthly" {if $option.frequency =='monthly'}selected="selected"{/if}>{l s='Monthly' mod='seooptimizer'}</option>
                            <option value="yearly" {if $option.frequency =='yearly'}selected="selected"{/if}>{l s='Yearly' mod='seooptimizer'}</option>
                            <option value="never" {if $option.frequency =='never'}selected="selected"{/if}>{l s='Never' mod='seooptimizer'}</option>
                        </select>
                    </div>
                </div>
            {/foreach}
        </div>
    {elseif $input.type == 'description'}
        <div class="col-lg-8">
            <div class="alert alert-info">
                {$input.content nofilter}
                {if isset($input.desc) && !empty($input.desc)}
                    <p class="help-block">
                        {if is_array($input.desc)}
                            {foreach $input.desc as $p}
                                {if is_array($p)}
                                    <span id="{$p.id}">{$p.text}</span><br />
                                {else}
                                    {$p}<br />
                                {/if}
                            {/foreach}
                        {else}
                            {$input.desc}
                        {/if}
                    </p>
                {/if}
            </div>
        </div>
    {elseif $input.type == 'button'}
        <div class="col-lg-8">
            <button class="btn btn-primary" {if isset($input.ajaxAction)}data-ajax-action="{$input.ajaxAction|escape:'htmlall':'UTF-8'}"{/if} {if isset($input.ajaxTarget)}data-ajax-target="{$input.ajaxTarget|escape:'htmlall':'UTF-8'}"{/if}>
                {$input.text|escape:'htmlall':'UTF-8'}
            </button>
            {if isset($input.desc) && !empty($input.desc)}
                <p class="help-block">
                    {if is_array($input.desc)}
                        {foreach $input.desc as $p}
                            {if is_array($p)}
                                <span id="{$p.id}">{$p.text}</span><br />
                            {else}
                                {$p}<br />
                            {/if}
                        {/foreach}
                    {else}
                        {$input.desc}
                    {/if}
                </p>
            {/if}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="description"}
    {$smarty.block.parent}
    {if $input.type == 'text' && isset($input.tags)}
        <div class="tags">
            {foreach $input.tags as $tag => $tooltip}
                <span class="label label-info label-tooltip" data-toggle="tooltip" data-html="true" title="{$tooltip|escape:'htmlall':'UTF-8'}">
                    {$tag|escape:'htmlall':'UTF-8'}
                </span>
            {/foreach}
        </div>
    {/if}
{/block}