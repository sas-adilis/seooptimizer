<?php

namespace Adilis\SeoOptimizer\Events;

abstract class AbstractDeleteRedirect implements DeleteRedirectInterface
{
    /**
     * @var \ObjectModel
     */
    protected $object;

    public function __construct(\ObjectModel $object)
    {
        $this->object = $object;
    }

    public function process()
    {
        if ($this->shouldRun()) {
            $redirections = $this->getRedirections();

            if (!count($redirections)) {
                return;
            }

            foreach ($redirections as &$redirection) {
                $redirection['redirect_type'] = 301;
                $redirection['id_shop'] = (int) \Context::getContext()->shop->id;
                $redirection['date_add'] = date('Y-m-d H:i:s');
                $redirection['date_upd'] = date('Y-m-d H:i:s');
            }

            $date_now = date('Y-m-d H:i:s');

            $query = '
                INSERT INTO ' . _DB_PREFIX_ . 'seooptimizer_redirect
                (`redirect_from`, `redirect_to`, `redirect_type`, `id_shop`, `date_add`) VALUES ';

            foreach ($redirections as $redirection) {
                $query .= sprintf(
                    '("%s", "%s", %d, %d, "%s"), ',
                    pSQL($redirection['redirect_from']),
                    pSQL($redirection['redirect_to']),
                    301,
                    (int) \Context::getContext()->shop->id,
                    pSQL($date_now)
                );
            }

            $query = rtrim($query, ', ');
            $query .= ' ON DUPLICATE KEY UPDATE `redirect_to` = VALUES(`redirect_to`), `redirect_type` = VALUES(`redirect_type`), `date_upd` = "' . pSQL($date_now) . '";';

            \Db::getInstance()->execute($query);
        }
    }
}
