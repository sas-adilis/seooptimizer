<?php

namespace Adilis\SeoOptimizer\Content\Report;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\EntityDefinition\EntityDefinitionInterface;
use Adilis\SeoOptimizer\TranslateHelper;

class ReportMissingImageLegend extends Report implements ReportInterface
{
    public $counter_by_product = [];

    public function getContent(): string
    {
        return $this->renderFormReport('Missing image legend', 'icon-picture');
    }

    /**
     * @throws \PrestaShopException
     */
    public function run(EntityDefinitionInterface $definition, array $rows = [], bool $shouldFix = false): array
    {
        $fixed = 0;
        $context = \Context::getContext();
        $founded_elements = [];
        foreach ($rows as $row) {
            foreach ($definition->getFields() as $field => $field_type) {
                if ($field_type !== Constants::LEGEND_FIELD) {
                    continue;
                }
                $legend = trim($row[$field] ?? '');

                if (empty($legend)) {
                    $legend_fixed = '';
                    if ($shouldFix) {
                        $legend_fixed = $this->fix($row);
                        if ($legend_fixed) {
                            ++$fixed;
                            $row[$field] = $legend_fixed;
                            $definition->updateRow($row);
                        }
                    }

                    /* todo: Image format configuration */
                    $founded_elements[] = [
                        'id_primary' => $row[$definition->getPrimaryKey()],
                        'image' => $context->link->getImageLink($row['product_link_rewrite'], $row['id_image'], 'medium_default'),
                        'product' => $context->link->getProductLink($row['id_product'], $row['id_lang'], $row['id_shop'] ?? null),
                        'new_legend' => $legend_fixed,
                    ];
                }
            }
        }

        return [$founded_elements, count($founded_elements), $fixed];
    }

    public function getReportFields(): array
    {
        // todo: manage translation
        return [
            'product' => TranslateHelper::get()->l('Product'),
            'image' => TranslateHelper::get()->l('Image'),
            'new_legend' => TranslateHelper::get()->l('New legend'),
        ];
    }

    public function getAllowedFieldsTypes(): array
    {
        return [
            Constants::LEGEND_FIELD,
        ];
    }

    private function fix($row)
    {
        if (\Configuration::get('SEOO_FIX_IMAGE_LEGEND_METHOD') == Constants::FIX_METHOD_TEXT) {
            $legend = \Configuration::get('SEOO_FIX_IMAGE_LEGEND_TEXT', $row['id_lang'], $row['id_shop'] ?? null);
            if (!isset($this->counter_by_product[$row['id_product']])) {
                $this->counter_by_product[$row['id_product']] = 1;
            }
            $legend_fixed = str_replace([
                '{product_title}',
                '{product_meta_title}',
                '{counter}'
            ],
            [
                $row['product_name'],
                $row['product_meta_title'],
                $this->counter_by_product[$row['id_product']]
            ], $legend);

            if (strpos($legend, '{counter}') !== false) {
                if ($this->legendExists($row, $legend_fixed)) {
                    while ($this->legendExists($row, $legend_fixed)) {
                        $legend_fixed = str_replace([
                            '{product_title}',
                            '{product_meta_title}',
                            '{counter}'
                        ],
                        [
                            $row['product_name'],
                            $row['product_meta_title'],
                            ++$this->counter_by_product[$row['id_product']]
                        ], $legend);
                    }
                }

            }
            return $legend_fixed;
        }

        return '';
    }

    private function legendExists(array $row, string $legend_fixed): bool {
        return \Db::getInstance()->getValue('
            SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'image_lang il
            INNER JOIN ' . _DB_PREFIX_ . 'image i ON i.id_image = il.id_image
            WHERE
                i.id_product = ' . (int)$row['id_product'] . ' AND
                il.id_lang = ' . (int)$row['id_lang'] . ' AND
                il.legend = "' . pSQL($legend_fixed) . '"'
        ) > 0;
    }
}
