<?php

namespace Adilis\SeoOptimizer\FormBuilderModifier;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Form\Type\SerpPreviewType;
use Adilis\SeoOptimizer\TranslateHelper;
use Context;
use Db;
use Language;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use SeoOptimizerPage;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryFormModifier
{
    /**
     * @var array
     */
    private $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function process()
    {
        if (!isset($this->params['form_builder']) || !($this->params['form_builder'] instanceof FormBuilderInterface)) {
            return;
        }

        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $this->params['form_builder'];
        $idEntity = isset($this->params['id']) ? (int) $this->params['id'] : 0;
        $t = TranslateHelper::get();

        // SERP Preview — only add if PS doesn't already provide one (seo_preview)
        $entityUrl = '';
        if ($idEntity > 0) {
            $entityUrl = Context::getContext()->link->getCategoryLink(
                $idEntity,
                null,
                (int) Context::getContext()->language->id
            );
        }
        if (!$formBuilder->has('seo_preview')) {
            $formBuilder->add('seoo_serp_preview', SerpPreviewType::class, [
                'entity_url' => $entityUrl,
            ]);
        }

        // Focus Keywords (translatable)
        $formBuilder->add('seoo_keywords', TranslatableType::class, [
            'label' => $t->l('Focus Keywords'),
            'help' => $t->l('Comma-separated keywords that this page should rank for. Used by the SEO audit to check keyword presence in title, description, headings and content.'),
            'type' => TextType::class,
            'required' => false,
            'options' => [
                'attr' => [
                    'placeholder' => $t->l('e.g. chaussures running, baskets sport'),
                ],
            ],
        ]);

        // Canonical URL (translatable)
        $formBuilder->add('seoo_canonical_url', TranslatableType::class, [
            'label' => $t->l('Canonical URL'),
            'help' => $t->l('Override the default canonical URL for this page. Useful to avoid duplicate content when the same page is accessible via multiple URLs. Leave empty to use the default URL. Cross-domain URLs are supported.'),
            'type' => TextType::class,
            'required' => false,
            'options' => [
                'attr' => [
                    'placeholder' => 'https://',
                ],
            ],
        ]);

        // Indexation
        $formBuilder->add('seoo_noindex', ChoiceType::class, [
            'label' => $t->l('Indexation'),
            'help' => $t->l('Prevent search engines from indexing this page. A "noindex" meta tag will be added to the page header. Useful for pages with thin or duplicate content.'),
            'required' => false,
            'placeholder' => false,
            'empty_data' => '0',
            'choices' => [
                $t->l('Default (index)') => 0,
                $t->l('Noindex — hide from search engines') => 1,
            ],
        ]);

        // Link following
        $formBuilder->add('seoo_nofollow', ChoiceType::class, [
            'label' => $t->l('Link following'),
            'help' => $t->l('Prevent search engines from following links on this page. A "nofollow" meta tag will be added to the page header. This does not affect indexation of the page itself.'),
            'required' => false,
            'placeholder' => false,
            'empty_data' => '0',
            'choices' => [
                $t->l('Default (follow)') => 0,
                $t->l('Nofollow — do not follow links') => 1,
            ],
        ]);

        // Redirection type
        $formBuilder->add('seoo_redirect_type', ChoiceType::class, [
            'label' => $t->l('Redirection'),
            'help' => $t->l('Set up a redirect from this page to another URL. Useful when a category is removed or merged. 301 is permanent (best for SEO), 302 is temporary, 404 and 410 signal the page no longer exists.'),
            'required' => false,
            'placeholder' => false,
            'empty_data' => '',
            'attr' => [
                'data-seoo-toggle-url' => '1',
            ],
            'choices' => [
                $t->l('No redirection') => '',
                $t->l('301 — Permanent redirect') => '301',
                $t->l('302 — Temporary redirect') => '302',
                $t->l('404 — Page not found') => '404',
                $t->l('410 — Page gone') => '410',
            ],
        ]);

        // Redirection target URL (only relevant for 301/302, hidden otherwise via JS)
        $formBuilder->add('seoo_redirect_url', TextType::class, [
            'label' => $t->l('Redirect target URL'),
            'help' => $t->l('The destination URL where visitors and search engines will be redirected.'),
            'required' => false,
            'attr' => [
                'placeholder' => 'https://',
            ],
        ]);

        // Pre-fill with existing data
        if ($idEntity > 0 && isset($this->params['data'])) {
            $keywordsData = [];
            $canonicalData = [];
            $noindex = 0;
            $nofollow = 0;

            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $config = SeoOptimizerPage::getSeoConfig('category', $idEntity, $idLang);
                $keywordsData[$idLang] = $config['keywords'];
                $canonicalData[$idLang] = $config['canonical_url'];
                if (empty($noindex) && (int) $config['noindex']) {
                    $noindex = 1;
                }
                if (empty($nofollow) && (int) $config['nofollow']) {
                    $nofollow = 1;
                }
            }

            $this->params['data']['seoo_keywords'] = $keywordsData;
            $this->params['data']['seoo_canonical_url'] = $canonicalData;
            $this->params['data']['seoo_noindex'] = $noindex;
            $this->params['data']['seoo_nofollow'] = $nofollow;

            // Pre-fill redirect from existing seooptimizer_redirect entry
            if ($entityUrl) {
                $parsedPath = parse_url($entityUrl, PHP_URL_PATH);
                if ($parsedPath) {
                    $redirect = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                        'SELECT redirect_type, redirect_to
                        FROM ' . _DB_PREFIX_ . 'seooptimizer_redirect
                        WHERE redirect_from = "' . pSQL($parsedPath) . '"'
                    );
                    if ($redirect) {
                        $this->params['data']['seoo_redirect_type'] = $redirect['redirect_type'];
                        $this->params['data']['seoo_redirect_url'] = $redirect['redirect_to'];
                    }
                }
            }
        }
    }
}
