let $seoDiv;
let $targetForm;

$(document).ready(function() {
   $targetForm = $('form[name=supplier]');
   if ($targetForm.length) {
       // Create a specific SEO DIV
        $seoDiv = $('<section class="card seo-optimizer"></section>');
        $seoDiv.append('<cl>SEO Optimizer</cl>');
        $targetForm.find('.card .card-text').append($seoDiv);

        moveFieldToSeoSection('supplier[meta_title]', true);
        moveFieldToSeoSection('supplier[meta_description]', true);
        moveFieldToSeoSection('supplier[meta_keyword]', true);
   }
});

function moveFieldToSeoSection(fieldName, is_lang = false) {
    const selector = is_lang ? `[name="${fieldName}[${default_language}]"]`: `[name="${fieldName}"]`;
    const $field = $targetForm.find(selector);
    if ($field.length) {
        const $fieldContainer = $field.closest('.form-group');
        $fieldContainer.detach();
        $fieldContainer.appendTo($seoDiv);
    }
}