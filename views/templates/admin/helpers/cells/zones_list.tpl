{if empty($cell_zones) || $cell_zones == '-' || $cell_zones == 'None'}
    <span class="seoo-cell-empty">{$cell_zones|escape:'htmlall':'UTF-8'}</span>
{else}
    {foreach $cell_zones_array as $zone}
        <span class="seoo-cell-zone">{$zone|escape:'htmlall':'UTF-8'}</span>
    {/foreach}
{/if}
