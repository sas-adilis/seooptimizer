<?php

namespace Adilis\SeoOptimizer\Content\DataList;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

class DataListPagesNotFound extends DataList implements DataListInterface
{
    public function getTable(): string
    {
        return 'seooptimizer_log_404';
    }

    public function getFields(): array
    {
        return [
            'id_seooptimizer_log_404' => [
                'title' => 'ID',
                'type' => 'int',
                'orderby' => true,
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'url' => [
                'title' => $this->l('URL'),
                'orderby' => true,
                'callback_object' => Utils::class,
                'callback' => 'displayTruncableLink',
            ],
            'referer' => [
                'title' => $this->l('Referer'),
                'orderby' => true,
                'callback_object' => Utils::class,
                'callback' => 'displayTruncableLink',
            ],
            'hits' => [
                'title' => $this->l('Hits'),
                'orderby' => true,
                'align' => 'center',
                'search' => false,
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'type' => 'datetime',
                'orderby' => true,
            ],
        ];
    }

    public function onBeforeGetList(\DbQuery $query)
    {
        $query->select('COUNT(*) as hits');
        $query->groupBy('url');
    }

    public function onBeforeGenerateList()
    {
        $this->helper->actions = ['delete', 'redirect'];

        $this->helper->displayRedirectLink = function () {
        };
    }

    public function postProcessDelete($id_primary = null, $redirect = true)
    {
        if (!$id_primary) {
            $id_primary = (int) \Tools::getValue('id_seooptimizer_log_404');
        }
        $query = new \DbQuery();
        $query->select('url');
        $query->from('seooptimizer_log_404');
        $query->where('id_seooptimizer_log_404 = ' . $id_primary);
        $url = \Db::getInstance()->getValue($query);

        if ($url) {
            \Db::getInstance()->delete('seooptimizer_log_404', 'url = "' . pSQL($url) . '"');
            if ($redirect) {
                \Tools::redirectAdmin(Utils::getConfigFormUrl(1));
            }
        } else {
            \Context::getContext()->controller->errors[] = $this->l('The 404 log does not exist');
        }
    }

    protected function getList(
        $order_by = null,
        $order_way = null,
        $start = 0,
        $limit = null
    ) {
        $list = parent::getList($order_by, $order_way, $start, $limit);
        foreach ($list as $key => $item) {
            $url_params = [
                'controller' => 'AdminModules',
                'configure' => 'seooptimizer',
                'create_redirection_from_404' => (int) $item['id_seooptimizer_log_404'],
                'show_tab' => 'tab-redirects',
            ];
            $list[$key]['redirect'] = sprintf(
                '<a href="%s"><i class="icon-share"></i> %s</a>',
                \Context::getContext()->link->getAdminLink('AdminModules', true, [], $url_params),
                $this->l('Create a redirection')
            );
        }

        return $list;
    }

    public function getTitle(): string
    {
        return $this->l('Pages not found');
    }

    public function getIcon(): string
    {
        return 'icon-unlink';
    }
}
