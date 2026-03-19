{if $cell_severity == 'critical'}
    {assign var='cell_color' value='#dc2626'}
{elseif $cell_severity == 'warning'}
    {assign var='cell_color' value='#f59e0b'}
{elseif $cell_severity == 'good'}
    {assign var='cell_color' value='#16a34a'}
{else}
    {assign var='cell_color' value='#6b7280'}
{/if}
<span class="seoo-cell-severity" style="background:{$cell_color}" title="{$cell_severity|escape:'htmlall':'UTF-8'}"></span>
