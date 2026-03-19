<?php

namespace Adilis\SeoOptimizer\FormBuilderModifier;

if (!defined('_PS_VERSION_')) {
    exit;
}


use Symfony\Component\Form\FormBuilder;

class SupplierFormModifier
{
    /**
     * @var array
     */
    private $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    private function getFields()
    {
        return [
            [
                'type' => 'select',
                'name' => 'sitemap_priority',
                'label' => 'Sitemap priority',
                'required' => true,
                'options' => [
                    'default' => [
                        'value' => null,
                        'label' => ('Pick an option'),
                    ],
                    'query' => [

                    ],
                    'id' => 'id',
                    'name' => 'name',
                ]
            ],
        ];
    }

    public function process()
    {
        if (isset($this->params['form_builder']) && $this->params['form_builder'] instanceof FormBuilder) {
            $formBuilder = $this->params['form_builder'];
            foreach ($this->getFields() as $field) {
                switch ($field['type']) {
                    case 'select':
                        $choices = [];
                        if (isset($field['options']['default'])) {
                            $choices[$field['options']['default']['label']] = $field['options']['default']['value'];
                        }
                        foreach ($field['options']['query'] as $option) {
                            $choices[$option[$field['options']['name']]] = $option[$field['options']['id']];
                        }
                        $formBuilder->add(
                            $field['name'],
                            \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
                            [
                                'label' => $field['label'],
                                'choices' => $choices,
                            ]
                        );
                        break;
                }
            }
        }
    }
}