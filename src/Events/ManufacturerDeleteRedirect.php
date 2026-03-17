<?php

namespace Adilis\SeoOptimizer\Events;

class ManufacturerDeleteRedirect extends AbstractDeleteRedirect implements DeleteRedirectInterface
{
    /**
     * @var \Manufacturer
     */
    protected $object;

    public function shouldRun(): bool
    {
        return
            $this->object instanceof \Manufacturer
            // && \Configuration::get('SEOO_REDIRECT_DELETED_MANUFACTURER')
        ;
    }

    public function getRedirections(): array
    {
        $context = \Context::getContext();
        $redirections = [];
        foreach (\Language::getLanguages() as $lang) {
            $redirect_from = $context->link->getManufacturerLink($this->object);
            $base_url = rtrim($context->shop->getBaseURL(true), '/');
            $redirect_from = str_replace($base_url, '', $redirect_from);
            $redirect_to = $context->link->getPageLink('manufacturer', null, $lang['id_lang']);

            $redirections[] = [
                'redirect_from' => $redirect_from,
                'redirect_to' => $redirect_to,
            ];
        }

        return $redirections;
    }
}
