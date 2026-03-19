{if empty($cell_zones) || $cell_zones == '-'}
    <span class="seoo-cell-empty">-</span>
{else}
    {foreach $cell_zones_array as $zone}
        <span class="seoo-cell-zone seoo-cell-zone--missing">{$zone|escape:'htmlall':'UTF-8'}</span>
    {/foreach}
{/if}
