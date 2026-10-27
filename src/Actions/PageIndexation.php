<?php

namespace Adilis\SeoOptimizer\Actions;

use Adilis\SeoOptimizer\Constants;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Applies the configured indexation action (noindex, 404, redirection)
 * on suppliers, manufacturers, stores and sitemap pages.
 */
class PageIndexation
{
    public function run()
    {
        $configurationKey = $this->getConfigurationKey((string) \Dispatcher::getInstance()->getController());
        if ($configurationKey === null) {
            return;
        }

        $context = \Context::getContext();
        $indexationAction = (int) \Configuration::get($configurationKey);

        switch ($indexationAction) {
            case Constants::PAGE_INDEXATION_NOINDEX:
                header('X-Robots-Tag: noindex');
                $page = $context->smarty->getTemplateVars('page');
                $page['meta']['robots'] = 'noindex';
                $context->smarty->assign('page', $page);
                break;
            case Constants::PAGE_INDEXATION_404:
                \Tools::redirect(
                    'pagenotfound',
                    __PS_BASE_URI__,
                    $context->link,
                    [
                        'HTTP/1.0 404 Not Found',
                    ]
                );
                break;
            case Constants::PAGE_INDEXATION_REDIRECT_301:
            case Constants::PAGE_INDEXATION_REDIRECT_302:
                $redirectUrl = \Configuration::get(str_replace('INDEXATION', 'REDIRECTION', $configurationKey));
                if (\Validate::isUrl($redirectUrl)) {
                    $httpCode = $indexationAction === Constants::PAGE_INDEXATION_REDIRECT_301 ? Constants::HTTP_CODE_301 : Constants::HTTP_CODE_302;
                    header('Location: ' . $redirectUrl, true, $httpCode);
                }
                break;
        }
    }

    private function getConfigurationKey(string $controller)
    {
        switch ($controller) {
            case 'stores':
                return 'SEOO_STORE_PAGE_INDEXATION';
            case 'manufacturer':
                return 'SEOO_MANUFACTURER_PAGE_INDEXATION';
            case 'supplier':
                return 'SEOO_SUPPLIER_PAGE_INDEXATION';
            case 'sitemap':
                return 'SEOO_SITEMAP_PAGE_INDEXATION';
        }

        return null;
    }
}
