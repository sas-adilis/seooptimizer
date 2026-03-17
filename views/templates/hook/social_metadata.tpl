<!-- SEOO : Social Metadata -->
<meta property="og:title" content="{$page.meta.title|escape:'html':'UTF-8'}"/>
<meta property="og:url" content="{$urls.current_url|escape:'html':'UTF-8'}"/>
<meta property="og:site_name" content="{$shop.name|escape:'html':'UTF-8'}"/>
<meta property="og:description" content="{$page.meta.description|escape:'html':'UTF-8'}">
<meta property="og:type" content="{$seoo_social_metadata_type|escape:'html':'UTF-8'}">
<meta property="og:image" content="{$seoo_social_metadata_image|escape:'html':'UTF-8'}" />
{if !empty($seoo_social_metadata_image_width)}
    <meta property="og:image:width" content="{$seoo_social_metadata_image_width|intval}" />
    <meta property="og:image:height" content="{$seoo_social_metadata_image_height|intval}" />
{/if}

{if $controller_name == 'product' && isset($product)}
    {if $product.show_price}
        <meta property="product:pretax_price:amount" content="{$product.price_tax_exc|floatval}">
        <meta property="product:pretax_price:currency" content="{$currency.iso_code|escape:'html':'UTF-8'}">
        <meta property="product:price:amount" content="{$product.price_amount|floatval}">
        <meta property="product:price:currency" content="{$currency.iso_code|escape:'html':'UTF-8'}">
    {/if}
    {if isset($product.weight) && ($product.weight != 0)}
        <meta property="product:weight:value" content="{$product.weight|floatval}">
        <meta property="product:weight:units" content="{$product.weight_unit|escape:'html':'UTF-8'}">
    {/if}
{/if}
<!-- /SEOO : Social Metadata -->


