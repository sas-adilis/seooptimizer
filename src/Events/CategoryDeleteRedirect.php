<?php

namespace Adilis\SeoOptimizer\Events;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CategoryDeleteRedirect extends AbstractDeleteRedirect implements DeleteRedirectInterface
{
    /**
     * @var \Category
     */
    protected $object;

    public function shouldRun(): bool
    {
        return
            $this->object instanceof \Category
            && \Configuration::get('SEOO_REDIRECT_DELETED_CATEGORY')
            && $this->object->is_root_category == 0
        ;
    }

    public function getRedirections(): array
    {
        $category_parent = new \Category($this->object->id_parent);
        if (!\Validate::isLoadedObject($category_parent) || $category_parent->is_root_category) {
            return [];
        }

        $context = \Context::getContext();
        $redirections = [];
        foreach (\Language::getLanguages() as $lang) {
            $link_rewrite_from = is_array($this->object->link_rewrite) ? $this->object->link_rewrite[$lang['id_lang']] : $this->object->link_rewrite;
            $redirect_from = $context->link->getCategoryLink($this->object, $link_rewrite_from);
            $base_url = rtrim($context->shop->getBaseURL(true), '/');
            $redirect_from = str_replace($base_url, '', $redirect_from);

            $link_rewrite_to = $category_parent->link_rewrite[$lang['id_lang']];
            $redirect_to = $context->link->getCategoryLink($category_parent, $link_rewrite_to);

            $redirections[] = [
                'redirect_from' => $redirect_from,
                'redirect_to' => $redirect_to,
            ];
        }

        return $redirections;
    }
}
