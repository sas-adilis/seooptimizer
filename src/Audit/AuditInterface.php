<?php

namespace Adilis\SeoOptimizer\Audit;

interface AuditInterface
{
    public function getKey(): string;

    public function getTitle(): string;

    public function getDescription(): string;

    public function getIcon(): string;

    /**
     * Filename of the visual illustration (relative to views/img/audits/).
     * Return empty string if no visual.
     *
     * @return string
     */
    public function getVisual(): string;

    /**
     * @return string[]
     */
    public function getObserverClasses(): array;

    /**
     * Column definitions for the result table.
     * Key = field name in result row, Value = column header label.
     *
     * @return array<string, string>
     */
    public function getResultColumns(): array;

    /**
     * KPI definitions for the audit card.
     * Each entry: ['key' => string, 'label' => string, 'type' => 'sum_results'|'count_severity'|'custom']
     * - 'sum_results': sums a field from all results
     * - 'count_severity': counts results matching a severity level (value = severity name)
     * - 'custom': static, computed by getCustomKpiValue()
     *
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array;

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array;
}
