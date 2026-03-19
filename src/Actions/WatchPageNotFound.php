<?php

namespace Adilis\SeoOptimizer\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class WatchPageNotFound
{
    public function run()
    {

        if (
            strstr($_SERVER['REQUEST_URI'], '404.php') &&
            isset($_SERVER['REDIRECT_URL'])
        ) {
            $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'];
        }
        if (
            !\Validate::isUrl($request_uri = $_SERVER['REQUEST_URI']) ||
            strstr($_SERVER['REQUEST_URI'], '-admin404')
        ) {
            return;
        }

        if (get_class(\Context::getContext()->controller) == 'PageNotFoundController') {
            $http_referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (
                empty($http_referer) ||
                \Validate::isAbsoluteUrl($http_referer)
            ) {
                \Db::getInstance()->insert('seooptimizer_log_404', [
                    'id_shop' => (int)\Context::getContext()->shop->id,
                    'id_shop_group' => (int)\Context::getContext()->shop->id_shop_group,
                    'url' => pSQL($request_uri),
                    'referer' => pSQL($http_referer),
                    'remote_ip' => \Tools::getRemoteAddr(),
                    'date_add' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
