{if $cell_score_value >= 80}
    {assign var='cell_color' value='#16a34a'}
{elseif $cell_score_value >= 50}
    {assign var='cell_color' value='#f59e0b'}
{else}
    {assign var='cell_color' value='#dc2626'}
{/if}
<span class="seoo-cell-score" style="background:{$cell_color}">{$cell_score_label|escape:'htmlall':'UTF-8'}</span>
