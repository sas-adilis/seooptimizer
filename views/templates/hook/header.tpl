{if $seoo_verification_code_google}
    <meta name="google-site-verification" content="{$seoo_verification_code_google|escape:'html':'UTF-8'}">
{/if}
{if $seoo_verification_code_bing}
    <meta name="msvalidate.01" content="{$seoo_verification_code_bing|escape:'html':'UTF-8'}">
{/if}
{if $seoo_verification_code_pinterest}
    <meta name="p:domain_verify" content="{$seoo_verification_code_pinterest|escape:'html':'UTF-8'}">
{/if}

{if !empty($page.alternate)}
    {foreach from=$page.alternate item=url key=language_code}
        <link rel="alternate" hreflang="{$language_code|escape:'html':'UTF-8'}" href="{$url|escape:'html':'UTF-8'}" />
    {/foreach}
{/if}