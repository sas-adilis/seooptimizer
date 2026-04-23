<?php

namespace Adilis\SeoOptimizer\FormHandler;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use Db;
use Language;
use SeoOptimizerPage;

/**
 * Handles saving SEO config (keywords, canonical_url, noindex, nofollow, redirect)
 * from Symfony form handler hooks (actionAfterUpdate/CreateCategoryFormHandler).
 */
class CategorySeoFormHandler
{
    /**
     * @var array
     */
    private $params;

    /**
     * @param array $params Hook params containing 'id' and 'form_data'
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function process()
    {
        $idEntity = isset($this->params['id']) ? (int) $this->params['id'] : 0;
        if ($idEntity <= 0) {
            return;
        }

        $formData = isset($this->params['form_data']) ? $this->params['form_data'] : [];

        $this->saveSeoConfig($idEntity, $formData);
        $this->saveRedirect($idEntity, $formData);
    }

    /**
     * Save keywords, canonical_url, noindex, nofollow per language.
     *
     * @param int $idEntity
     * @param array $formData
     */
    private function saveSeoConfig(int $idEntity, array $formData)
    {
        $keywords = isset($formData['seoo_keywords']) ? $formData['seoo_keywords'] : [];
        $canonical = isset($formData['seoo_canonical_url']) ? $formData['seoo_canonical_url'] : [];
        $noindex = isset($formData['seoo_noindex']) ? (int) $formData['seoo_noindex'] : 0;
        $nofollow = isset($formData['seoo_nofollow']) ? (int) $formData['seoo_nofollow'] : 0;

        if (!is_array($keywords) && !is_array($canonical)) {
            return;
        }

        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            SeoOptimizerPage::saveSeoConfig('category', $idEntity, [
                'keywords' => is_array($keywords) && isset($keywords[$idLang])
                    ? (string) $keywords[$idLang]
                    : '',
                'canonical_url' => is_array($canonical) && isset($canonical[$idLang])
                    ? (string) $canonical[$idLang]
                    : '',
                'noindex' => $noindex,
                'nofollow' => $nofollow,
            ], $idLang);
        }
    }

    /**
     * Save or delete the redirect rule for this category.
     *
     * @param int $idEntity
     * @param array $formData
     */
    private function saveRedirect(int $idEntity, array $formData)
    {
        $redirectType = isset($formData['seoo_redirect_type']) ? trim($formData['seoo_redirect_type']) : '';
        $redirectUrl = isset($formData['seoo_redirect_url']) ? trim($formData['seoo_redirect_url']) : '';

        // Build the category path (redirect_from stores the relative path)
        $idLang = (int) Context::getContext()->language->id;
        $categoryUrl = Context::getContext()->link->getCategoryLink($idEntity, null, $idLang);
        $redirectFrom = parse_url($categoryUrl, PHP_URL_PATH);

        if (!$redirectFrom) {
            return;
        }

        $db = Db::getInstance();
        $escapedFrom = pSQL($redirectFrom);

        // Find existing redirect for this URL
        $existing = $db->getValue(
            'SELECT id_seooptimizer_redirect FROM ' . _DB_PREFIX_ . 'seooptimizer_redirect
            WHERE redirect_from = "' . $escapedFrom . '"'
        );

        if (empty($redirectType)) {
            // No redirection selected — delete if exists
            if ($existing) {
                $db->delete('seooptimizer_redirect', 'id_seooptimizer_redirect = ' . (int) $existing);
            }
            return;
        }

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $db->update('seooptimizer_redirect', [
                'redirect_type' => pSQL($redirectType),
                'redirect_to' => pSQL($redirectUrl, true),
            ], 'id_seooptimizer_redirect = ' . (int) $existing);
        } else {
            $db->insert('seooptimizer_redirect', [
                'redirect_from' => $escapedFrom,
                'redirect_to' => pSQL($redirectUrl, true),
                'redirect_type' => pSQL($redirectType),
                'date_add' => $now,
            ]);
        }
    }
}
