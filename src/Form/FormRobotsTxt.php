<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\Utils;

class FormRobotsTxt extends FormAbstract implements FormInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    private static $presets = [
        'standard' => [
            'name' => 'Standard',
            'icon' => '<i class="icon-shield" style="font-size:28px;color:#05808B;"></i>',
            'desc' => 'Suits most PrestaShop shops',
            'recommended' => '1',
        ],
        'strict' => [
            'name' => 'Strict',
            'icon' => '<i class="icon-lock" style="font-size:28px;color:#05808B;"></i>',
            'desc' => 'Large catalogs with faceted navigation',
        ],
        'multilang' => [
            'name' => 'Multilingual',
            'icon' => '<i class="icon-globe" style="font-size:28px;color:#05808B;"></i>',
            'desc' => 'Multi-language shops with hreflang',
        ],
        'maintenance' => [
            'name' => 'Pre-launch',
            'icon' => '<i class="icon-warning" style="font-size:28px;color:#d97706;"></i>',
            'desc' => 'Blocks the entire site before going live',
        ],
    ];

    public function getContent(): string
    {
        $robots_txt_content = \Tools::getValue(
            'SEOO_ROBOTS_TXT',
            $this->getRobotsTxtContent()
        );

        $context = \Context::getContext();
        $shopUrl = rtrim($context->shop->getBaseURL(true), '/');

        $context->smarty->assign([
            'seoo_module_path' => __PS_BASE_URI__ . 'modules/seooptimizer/',
            'seoo_robots_content' => $robots_txt_content,
            'seoo_robots_presets' => self::$presets,
            'seoo_robots_presets_js' => json_encode($this->getPresetsContent()),
            'seoo_robots_form_action' => $context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => Utils::MODULE_NAME, 'module_name' => Utils::MODULE_NAME]
            ),
            'seoo_robots_token' => \Tools::getAdminTokenLite('AdminModules'),
            'seoo_robots_live_url' => $shopUrl . '/robots.txt',
            'seoo_shop_url' => $shopUrl,
            'seoo_robots_history' => $this->getHistory(),
        ]);

        return $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'seooptimizer/views/templates/admin/robots-txt.tpl'
        );
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function postProcess()
    {
        $content = \Tools::getValue('SEOO_ROBOTS_TXT');
        $content = str_replace("\r\n", "\n", $content);
        $context = \Context::getContext();

        $content_before = $this->getRobotsTxtContent();

        if ($content === $content_before) {
            $context->controller->errors[] = $this->l('The content of the robots.txt file has not been modified');

            return;
        }

        // Save backup in cache
        CacheManager::write(
            sprintf('robots_%s.txt', date('Y-m-d-H-i-s')),
            $content_before
        );

        if (file_put_contents(_PS_ROOT_DIR_ . '/robots.txt', $content)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            $context->controller->errors[] = $this->l('An error occurred while writing the robots.txt file');
        }
    }

    /**
     * @throws \PrestaShopException
     */
    public function postProcessReset()
    {
        $content_before = $this->getRobotsTxtContent();
        $context = \Context::getContext();

        CacheManager::write(
            sprintf('robots_%s.txt', date('Y-m-d-H-i-s')),
            $content_before
        );

        if (\Tools::generateRobotsFile(true)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            $context->controller->errors[] = $this->l('An error occurred while writing the robots.txt file');
        }
    }

    public function process()
    {
        // Handle restore from history
        $restoreFile = \Tools::getValue('restoreRobots');
        if ($restoreFile) {
            $this->processRestore($restoreFile);
        }

        parent::process();
    }

    /**
     * @param string $filename
     */
    private function processRestore(string $filename)
    {
        $context = \Context::getContext();

        // Validate filename to prevent path traversal
        $safeFilename = basename($filename);
        if ($safeFilename !== $filename || strpos($filename, '..') !== false) {
            $context->controller->errors[] = $this->l('Invalid filename.');
            return;
        }

        $cacheDir = _PS_ROOT_DIR_ . '/var/cache/seooptimizer/';
        $filePath = $cacheDir . $safeFilename;

        if (!file_exists($filePath)) {
            $context->controller->errors[] = $this->l('Backup file not found.');
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $context->controller->errors[] = $this->l('Unable to read backup file.');
            return;
        }

        // Save current content as backup before restoring
        $content_before = $this->getRobotsTxtContent();
        CacheManager::write(
            sprintf('robots_%s.txt', date('Y-m-d-H-i-s')),
            $content_before
        );

        if (file_put_contents(_PS_ROOT_DIR_ . '/robots.txt', $content)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            $context->controller->errors[] = $this->l('An error occurred while restoring the robots.txt file');
        }
    }

    /**
     * @return string
     */
    private function getRobotsTxtContent(): string
    {
        $content = \Tools::file_get_contents(_PS_ROOT_DIR_ . '/robots.txt');
        if ($content === false) {
            $content = '';
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    private function getPresetsContent(): array
    {
        return [
            'standard' => 'User-agent: *

# -- Technical pages --
Disallow: /modules/
Disallow: /classes/
Disallow: /tools/
Disallow: /translations/
Disallow: /upload/
Disallow: /download/
Disallow: /mails/
Disallow: /themes/

# -- Pages with no SEO value --
Disallow: /recherche
Disallow: /search
Disallow: /commande
Disallow: /order
Disallow: /panier
Disallow: /cart
Disallow: /mon-compte
Disallow: /my-account
Disallow: /historique-commandes
Disallow: /order-history
Disallow: /identite
Disallow: /identity
Disallow: /adresses
Disallow: /addresses
Disallow: /connexion
Disallow: /login
Disallow: /recuperation-mot-de-passe
Disallow: /password-recovery

# -- Params generating duplicate content --
Disallow: /*?order=
Disallow: /*?q=
Disallow: /*&order=
Disallow: /*&q=
Disallow: /*?page=
Disallow: /*&page=

# -- Sitemap --
Sitemap: __SHOP_URL__/sitemap.xml',

            'strict' => 'User-agent: *

# -- Technical pages --
Disallow: /modules/
Disallow: /classes/
Disallow: /tools/
Disallow: /translations/
Disallow: /upload/
Disallow: /download/
Disallow: /mails/
Disallow: /themes/

# -- Pages with no SEO value --
Disallow: /recherche
Disallow: /search
Disallow: /commande
Disallow: /order
Disallow: /panier
Disallow: /cart
Disallow: /mon-compte
Disallow: /my-account
Disallow: /historique-commandes
Disallow: /order-history
Disallow: /connexion
Disallow: /login
Disallow: /recuperation-mot-de-passe
Disallow: /password-recovery

# -- Sort / filter params --
Disallow: /*?order=
Disallow: /*?q=
Disallow: /*&order=
Disallow: /*&q=
Disallow: /*?page=
Disallow: /*&page=

# -- Aggressive facet blocking --
Disallow: /*?id_attribute*
Disallow: /*?id_feature*
Disallow: /*?color=
Disallow: /*?size=
Disallow: /*?price=
Disallow: /*?properties=
Disallow: /*?from-xhr*

# -- Comparison pages --
Disallow: /comparaison
Disallow: /products-comparison

# -- Technical views / AJAX --
Disallow: /*?content_only=
Disallow: /*?ajax=
Disallow: /*?back=
Disallow: /*?token=

# -- Sitemap --
Sitemap: __SHOP_URL__/sitemap.xml',

            'multilang' => 'User-agent: *

# -- Technical pages --
Disallow: /modules/
Disallow: /classes/
Disallow: /tools/
Disallow: /translations/
Disallow: /upload/
Disallow: /download/
Disallow: /mails/
Disallow: /themes/

# -- Pages with no SEO value --
Disallow: /recherche
Disallow: /search
Disallow: /commande
Disallow: /order
Disallow: /panier
Disallow: /cart
Disallow: /mon-compte
Disallow: /my-account
Disallow: /connexion
Disallow: /login

# -- Sort / filter params --
Disallow: /*?order=
Disallow: /*?q=
Disallow: /*?page=

# -- Language/currency switch params --
Disallow: /*?isolang=
Disallow: /*?id_lang=
Disallow: /*?SubmitCurrency=
Disallow: /*?id_currency=

# -- Sitemaps per language --
Sitemap: __SHOP_URL__/sitemap.xml',

            'maintenance' => '# WARNING: PRE-LAUNCH MODE
# The entire site is blocked for crawlers.
# Remember to change preset before going live!

User-agent: *
Disallow: /

# Sitemap ready for launch
Sitemap: __SHOP_URL__/sitemap.xml',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getHistory(): array
    {
        $cacheDir = _PS_ROOT_DIR_ . '/var/cache/seooptimizer/';
        $history = [];

        if (!is_dir($cacheDir)) {
            return $history;
        }

        $files = glob($cacheDir . 'robots_*.txt');
        if (!is_array($files)) {
            return $history;
        }

        // Sort by modification time descending
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Limit to 10 entries
        $files = array_slice($files, 0, 10);

        foreach ($files as $file) {
            $filename = basename($file);
            $mtime = filemtime($file);

            $history[] = [
                'file' => $filename,
                'date' => date('d/m/Y H:i:s', $mtime),
            ];
        }

        return $history;
    }
}
