<?php

namespace Adilis\SeoOptimizer\Events;

class ProductDeleteRedirect extends AbstractDeleteRedirect implements DeleteRedirectInterface
{
    /**
     * @var \Product
     */
    protected $object;

    public function shouldRun(): bool
    {
        return
            $this->object instanceof \Product
            && \Configuration::get('SEOO_REDIRECT_DELETED_PRODUCT')
        ;
    }

    /**
     * @throws \PrestaShopException
     */
    public function getRedirections(): array
    {
        $category_parent = new \Category($this->object->id_category_default);
        if (!\Validate::isLoadedObject($category_parent) || $category_parent->is_root_category) {
            return [];
        }

        $context = \Context::getContext();
        $redirections = [];
        foreach (\Language::getLanguages() as $lang) {
            $link_rewrite = is_array($this->object->link_rewrite) ? $this->object->link_rewrite[$lang['id_lang']] : $this->object->link_rewrite;
            $category_link_rewrite = $category_parent[$lang['id_lang']];
            $redirect_from = $context->link->getProductLink($this->object, $link_rewrite, $category_link_rewrite);
            $base_url = rtrim($context->shop->getBaseURL(true), '/');
            $redirect_from = str_replace($base_url, '', $redirect_from);
            $redirect_to = $context->link->getCategoryLink($category_parent, $category_link_rewrite);

            $redirections[] = [
                'redirect_from' => $redirect_from,
                'redirect_to' => $redirect_to,
            ];
        }

        return $redirections;
    }
}
