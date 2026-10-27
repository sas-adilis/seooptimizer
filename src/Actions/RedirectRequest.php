<?php

namespace Adilis\SeoOptimizer\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Executes the manual redirections (301/302/404/410) matching the requested URI.
 */
class RedirectRequest
{
    public function run()
    {
        $redirect = \Db::getInstance()->getRow('
            SELECT *
            FROM ' . _DB_PREFIX_ . 'seooptimizer_redirect WHERE
            redirect_from = "' . pSQL($_SERVER['REQUEST_URI']) . '"'
        );

        if (!$redirect) {
            return;
        }

        $type = (int) $redirect['redirect_type'];

        if ($type === 410) {
            header('HTTP/1.1 410 Gone');
            exit;
        }

        if ($type === 404) {
            header('HTTP/1.1 404 Not Found');
            $_GET['controller'] = 'pagenotfound';

            return;
        }

        if (!empty($redirect['redirect_to']) && \Validate::isUrl($redirect['redirect_to'])) {
            \Tools::redirect(
                $redirect['redirect_to'],
                __PS_BASE_URI__,
                \Context::getContext()->link,
                $type === 301 ? 'HTTP/1.1 301 Moved Permanently' : 'HTTP/1.1 302 Moved Temporarily'
            );
        }
    }
}
