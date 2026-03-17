<?php

namespace Adilis\SeoOptimizer\Form;

interface FormInterface
{
    public function getKey($to_underscore_case = false): string;

    public function renderForm($form, $fields_value = []): string;

    public function getContent(): string;

    public function process();
}
