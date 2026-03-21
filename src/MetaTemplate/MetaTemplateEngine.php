<?php

namespace Adilis\SeoOptimizer\MetaTemplate;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Entity\EntityRegistry;

class MetaTemplateEngine
{
    /**
     * Get tag definitions for all entity types (delegates to EntityRegistry).
     *
     * @return array<string, array<string, string>>
     */
    public static function getTagDefinitions(): array
    {
        return EntityRegistry::getTagDefinitions();
    }

    /**
     * Apply meta templates to the current page if meta title/description is empty.
     * Modifies the Smarty $page variable directly.
     */
    public static function apply(): void
    {
        $context = \Context::getContext();
        $controller = \Dispatcher::getInstance()->getController();
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;

        $entity = EntityRegistry::getByController($controller);
        if (!$entity) {
            return;
        }

        $idEntity = (int) \Tools::getValue($entity->getIdParam());
        if (!$idEntity) {
            return;
        }

        $emptyTitle = $entity->isMetaTitleEmpty($idEntity, $idLang, $idShop);
        $emptyDesc = $entity->isMetaDescriptionEmpty($idEntity, $idLang, $idShop);

        if (!$emptyTitle && !$emptyDesc) {
            return;
        }

        $page = $context->smarty->getTemplateVars('page');
        if (!is_array($page) || !isset($page['meta'])) {
            return;
        }

        $tags = $entity->resolveTagValues($idEntity, $idLang, $idShop);
        if (empty($tags)) {
            return;
        }

        $type = strtoupper($entity->getType());
        $modified = false;

        if ($emptyTitle) {
            $tpl = \Configuration::get('SEOO_META_TPL_' . $type . '_TITLE', $idLang);
            if (!empty($tpl)) {
                $page['meta']['title'] = self::processTemplate($tpl, $tags);
                $modified = true;
            }
        }

        if ($emptyDesc) {
            $tpl = \Configuration::get('SEOO_META_TPL_' . $type . '_DESC', $idLang);
            if (!empty($tpl)) {
                $page['meta']['description'] = self::processTemplate($tpl, $tags);
                $modified = true;
            }
        }

        if ($modified) {
            $context->smarty->assign('page', $page);
        }
    }

    /**
     * @param string $template
     * @param array<string, string> $tags
     * @return string
     */
    public static function processTemplate(string $template, array $tags): string
    {
        $result = str_replace(array_keys($tags), array_values($tags), $template);

        $result = preg_replace('/\s+/', ' ', trim($result));
        $result = preg_replace('/\s*[|\-–—]\s*$/', '', $result);
        $result = preg_replace('/^\s*[|\-–—]\s*/', '', $result);

        return trim($result);
    }
}
