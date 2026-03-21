<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Entity\EntityRegistry;
use Adilis\SeoOptimizer\Utils;

class FormMetaTemplates extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        $html = '';
        $entities = EntityRegistry::getAll();

        foreach ($entities as $type => $entity) {
            $html .= $this->renderEntityForm($type, $entity->getLabel(), $entity->getAvailableTags());
        }

        return $html;
    }

    /**
     * @param string $type
     * @param string $label
     * @param array<string, string> $tags
     * @return string
     */
    private function renderEntityForm(string $type, string $label, array $tags): string
    {
        $key = strtoupper($type);
        $tagsList = [];
        foreach ($tags as $tag => $desc) {
            $tagsList[] = '<code>' . $tag . '</code> — ' . $desc;
        }
        $tagsHtml = implode(' &nbsp;|&nbsp; ', $tagsList);

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l($label),
                    'icon' => 'icon-pencil',
                ],
                'description' => $this->l('Define meta title and description templates for pages that don\'t have custom meta defined. Available tags:') . '<br>' . $tagsHtml,
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'SEOO_META_TPL_' . $key . '_TITLE',
                        'label' => $this->l('Meta title template'),
                        'lang' => true,
                        'hint' => $this->l('Example:') . ' {name} | {shop_name}',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'SEOO_META_TPL_' . $key . '_DESC',
                        'label' => $this->l('Meta description template'),
                        'lang' => true,
                        'rows' => 3,
                        'hint' => $this->l('Example:') . ' ' . $this->getDefaultHint($type),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey() . ucfirst($type),
                ],
            ],
        ], $this->getFieldsValues($key));
    }

    /**
     * @param string $key
     * @return array
     */
    private function getFieldsValues(string $key): array
    {
        $values = [];
        $languages = \Language::getLanguages(false);

        foreach (['TITLE', 'DESC'] as $suffix) {
            $configKey = 'SEOO_META_TPL_' . $key . '_' . $suffix;
            foreach ($languages as $lang) {
                $idLang = (int) $lang['id_lang'];
                $values[$configKey][$idLang] = \Configuration::get($configKey, $idLang) ?: '';
            }
        }

        return $values;
    }

    /**
     * Dynamic postProcess handler for any entity type.
     * Called by FormAbstract::process() via method_exists() for submitFormMetaTemplates{Type}.
     *
     * @param string $name
     * @param array $arguments
     */
    public function __call(string $name, array $arguments)
    {
        if (strpos($name, 'postProcess') === 0) {
            $type = strtoupper(substr($name, 11));
            $entity = EntityRegistry::get(strtolower($type));
            if ($entity) {
                $this->saveEntityTemplates($type);
            }
        }
    }

    /**
     * @param string $key
     */
    private function saveEntityTemplates(string $key)
    {
        $languages = \Language::getLanguages(false);

        foreach (['TITLE', 'DESC'] as $suffix) {
            $configKey = 'SEOO_META_TPL_' . $key . '_' . $suffix;
            $values = [];
            foreach ($languages as $lang) {
                $idLang = (int) $lang['id_lang'];
                $values[$idLang] = \Tools::getValue($configKey . '_' . $idLang, '');
            }
            \Configuration::updateValue($configKey, $values);
        }
    }

    /**
     * @param string $type
     * @return string
     */
    private function getDefaultHint(string $type): string
    {
        switch ($type) {
            case 'product':
                return '{name} de {manufacturer}. {description_short} - {shop_name}';
            case 'category':
                return '{name} - {description} | {shop_name}';
            case 'cms':
                return '{title} | {shop_name}';
            case 'manufacturer':
                return '{name} - {description} | {shop_name}';
            case 'supplier':
                return '{name} - {description} | {shop_name}';
            default:
                return '';
        }
    }
}
