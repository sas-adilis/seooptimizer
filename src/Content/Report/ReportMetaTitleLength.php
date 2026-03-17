<?php

namespace Adilis\SeoOptimizer\Content\Report;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\EntityDefinition\EntityDefinitionInterface;

class ReportMetaTitleLength extends Report implements ReportInterface
{
    public function getContent(): string
    {
        return $this->renderFormReport('Meta title length', 'icon-align-left');
    }

    public function run(EntityDefinitionInterface $definition, array $rows = [], bool $shouldFix = false): array
    {
        $founded_elements = [];
        foreach ($rows as $row) {
            foreach ($definition->getFields() as $field => $field_type) {
                if ($field_type !== Constants::META_TITLE_FIELD) {
                    continue;
                }

                $value = $row[$field] ?? '';
                if (
                    strlen($value) < (int) \Configuration::get('SEO_OPTIMIZER_META_TITLE_MIN_LENGTH')
                    || strlen($value) > (int) \Configuration::get('SEO_OPTIMIZER_META_TITLE_MAX_LENGTH')
                ) {
                    $founded_elements[] = [
                        'id_primary' => $row[$definition->getPrimaryKey()],
                        'page' => $definition->getLink($row[$definition->getPrimaryKey()], $row['id_lang'], $row['id_shop'] ?? null),
                        'title' => $value,
                        'length' => strlen($value),
                    ];
                }
            }
        }

        return [$founded_elements, count($founded_elements), 0];
    }

    public function getReportFields(): array
    {
        return [
            'page' => 'Page',
            'title' => 'Meta title',
            'length' => 'Longueur',
        ];
    }

    public function getAllowedFieldsTypes(): array
    {
        return [
            Constants::META_TITLE_FIELD,
        ];
    }
}
