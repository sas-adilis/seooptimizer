<?php

namespace Adilis\SeoOptimizer\FormBuilderModifier\Type;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Display-only form type that renders a Google SERP preview.
 *
 * The preview updates live via JS by reading the native meta_title
 * and meta_description fields on the same form.
 */
class SerpPreviewType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'label' => false,
            'entity_url' => '',
            'form_theme' => '@Twig/seoo_serp_preview.html.twig',
        ]);

        $resolver->setAllowedTypes('entity_url', 'string');
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entity_url'] = $options['entity_url'];
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'seoo_serp_preview';
    }
}
