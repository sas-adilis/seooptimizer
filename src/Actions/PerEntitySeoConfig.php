<?php

namespace Adilis\SeoOptimizer\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Applies per-entity noindex/nofollow and custom canonical from the SEO config panel.
 * Must run AFTER CanonicalUrlGenerate so a custom canonical overrides the auto-generated one.
 */
class PerEntitySeoConfig
{
    public function run()
    {
        try {
            $controller = \Dispatcher::getInstance()->getController();
            $entityTypes = ['product', 'category', 'manufacturer', 'supplier', 'cms'];

            if (!in_array($controller, $entityTypes, true)) {
                return;
            }

            $idEntity = (int) \Tools::getValue('id_' . $controller);
            if ($idEntity <= 0) {
                return;
            }

            $smarty = \Context::getContext()->smarty;
            $config = \SeoOptimizerPage::getSeoConfig($controller, $idEntity);

            if ((int) $config['noindex'] === 1) {
                header('X-Robots-Tag: noindex');
                $this->addRobotsDirective($smarty, 'noindex');
            }

            if ((int) $config['nofollow'] === 1) {
                $this->addRobotsDirective($smarty, 'nofollow');
            }

            if (!empty($config['canonical_url'])) {
                $page = $smarty->getTemplateVars('page');
                $page['canonical'] = $config['canonical_url'];
                $smarty->assign('page', $page);
            }
        } catch (\Throwable $e) {
            // Columns may not exist yet (pre-upgrade) — silently skip
        }
    }

    /**
     * @param \Smarty $smarty
     * @param string $directive
     */
    private function addRobotsDirective($smarty, string $directive)
    {
        $page = $smarty->getTemplateVars('page');
        $robots = isset($page['meta']['robots']) ? $page['meta']['robots'] : '';
        if (strpos($robots, $directive) === false) {
            $page['meta']['robots'] = $robots ? $robots . ', ' . $directive : $directive;
        }
        $smarty->assign('page', $page);
    }
}
