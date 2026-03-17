<?php

namespace Adilis\SeoOptimizer\Content\Report;

interface ReportInterface
{
    public function getKey($to_underscore_case = false): string;

    public function renderForm(array $form, array $fields_value = []): string;

    public function process();

    public function getContent(): string;

    public function getReportFields(): array;

    public function canFix(): bool;

    public function getAllowedFieldsTypes(): array;
}
