<div id="searchRichSnippetResult">
    <table class="table">
        <thead>
            <tr class="nodrag nodrop">
                <th class="">
                    <span class="title_box active">{l s='File' mod='seooptimizer'}</span>
                </th>
                <th class="fixed-width-xs center text">
                    <span class="title_box">{l s='Line' mod='seooptimizer'}</span>
                </th>
            </tr>
        </thead>

        <tbody>
        {foreach $items as $item}
            <tr class="{cycle values=",odd"}">
                <td>{$item.file}</td>
                <td class="center">
                    {$item.line|intval}
                </td>
            </tr>
        {/foreach}
        <tr class=" odd">


        </tbody>
    </table>
    <small>
        <em>
            {l s='Scan date:' mod='seoptomizer'}{$date|escape:'html':'UTF-8'},
            {l s='Scan duration:' mod='seoptomizer'}{$duration|escape:'html':'UTF-8'}s
        </em>
    </small>
</div>