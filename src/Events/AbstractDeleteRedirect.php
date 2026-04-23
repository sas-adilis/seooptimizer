<?php

namespace Adilis\SeoOptimizer\Events;

if (!defined('_PS_VERSION_')) {
    exit;
}

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

            $date_now = date('Y-m-d H:i:s');

            $query = '
                INSERT INTO ' . _DB_PREFIX_ . 'seooptimizer_redirect
                (`redirect_from`, `redirect_to`, `redirect_type`, `date_add`) VALUES ';

            foreach ($redirections as $redirection) {
                $query .= sprintf(
                    '("%s", "%s", %d, "%s"), ',
                    pSQL($redirection['redirect_from']),
                    pSQL($redirection['redirect_to']),
                    301,
                    pSQL($date_now)
                );
            }

            $query = rtrim($query, ', ');
            $query .= ' ON DUPLICATE KEY UPDATE `redirect_to` = VALUES(`redirect_to`), `redirect_type` = VALUES(`redirect_type`);';

            \Db::getInstance()->execute($query);
        }
    }
}
