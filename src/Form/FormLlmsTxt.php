<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\Utils;

class FormLlmsTxt extends FormAbstract implements FormInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    private static $presets = [
        'ecommerce' => [
            'name' => 'E-commerce',
            'icon' => '<i class="icon-shopping-cart" style="font-size:28px;color:#05808B;"></i>',
            'desc' => 'Standard for online shops with products and categories',
            'recommended' => '1',
        ],
        'minimal' => [
            'name' => 'Minimal',
            'icon' => '<i class="icon-minus-circle" style="font-size:28px;color:#05808B;"></i>',
            'desc' => 'Basic info only, limits content exposure',
        ],
        'detailed' => [
            'name' => 'Detailed',
            'icon' => '<i class="icon-list-alt" style="font-size:28px;color:#05808B;"></i>',
            'desc' => 'Full documentation with all sections',
        ],
        'block' => [
            'name' => 'Block AI',
            'icon' => '<i class="icon-ban-circle" style="font-size:28px;color:#d97706;"></i>',
            'desc' => 'Opt-out from AI training and indexing',
        ],
    ];

    public function getContent(): string
    {
        $content = \Tools::getValue(
            'SEOO_LLMS_TXT',
            $this->getLlmsTxtContent()
        );

        $context = \Context::getContext();
        $shopUrl = rtrim($context->shop->getBaseURL(true), '/');
        $shopName = \Configuration::get('PS_SHOP_NAME');

        $context->smarty->assign([
            'seoo_module_path' => __PS_BASE_URI__ . 'modules/seooptimizer/',
            'seoo_llms_content' => $content,
            'seoo_llms_presets' => self::$presets,
            'seoo_llms_presets_js' => json_encode($this->getPresetsContent($shopName, $shopUrl)),
            'seoo_llms_form_action' => $context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => Utils::MODULE_NAME, 'module_name' => Utils::MODULE_NAME]
            ),
            'seoo_llms_token' => \Tools::getAdminTokenLite('AdminModules'),
            'seoo_llms_live_url' => $shopUrl . '/llms.txt',
            'seoo_shop_url' => $shopUrl,
            'seoo_shop_name' => $shopName,
            'seoo_llms_exists' => file_exists(_PS_ROOT_DIR_ . '/llms.txt'),
            'seoo_llms_history_html' => $this->renderHistoryList(),
        ]);

        return $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'seooptimizer/views/templates/admin/llms-txt.tpl'
        );
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function process()
    {
        $restoreFile = \Tools::getValue('restoreLlms');
        if ($restoreFile) {
            $this->processRestore($restoreFile);
        }

        $deleteFile = \Tools::getValue('deleteLlmsHistory');
        if ($deleteFile) {
            $this->processDeleteHistory($deleteFile);
        }

        parent::process();
    }

    public function postProcess()
    {
        $content = \Tools::getValue('SEOO_LLMS_TXT');
        $content = str_replace("\r\n", "\n", $content);

        // Backup current file before overwriting
        $currentContent = $this->getLlmsTxtContent();
        if ($currentContent !== '') {
            CacheManager::write(
                sprintf('llms_%s.txt', date('Y-m-d-H-i-s')),
                $currentContent
            );
        }

        if (file_put_contents(_PS_ROOT_DIR_ . '/llms.txt', $content)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            \Context::getContext()->controller->errors[] = $this->l('An error occurred while writing the llms.txt file');
        }
    }

    public function postProcessDelete()
    {
        $context = \Context::getContext();
        $path = _PS_ROOT_DIR_ . '/llms.txt';

        // Backup before deleting
        $currentContent = $this->getLlmsTxtContent();
        if ($currentContent !== '') {
            CacheManager::write(
                sprintf('llms_%s.txt', date('Y-m-d-H-i-s')),
                $currentContent
            );
        }

        if (file_exists($path)) {
            if (unlink($path)) {
                \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
            } else {
                $context->controller->errors[] = $this->l('An error occurred while deleting the llms.txt file');
            }
        } else {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        }
    }

    /**
     * @param string $filename
     */
    private function processRestore(string $filename)
    {
        $context = \Context::getContext();
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

        // Backup current before restoring
        $currentContent = $this->getLlmsTxtContent();
        if ($currentContent !== '') {
            CacheManager::write(
                sprintf('llms_%s.txt', date('Y-m-d-H-i-s')),
                $currentContent
            );
        }

        if (file_put_contents(_PS_ROOT_DIR_ . '/llms.txt', $content)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            $context->controller->errors[] = $this->l('An error occurred while restoring the llms.txt file');
        }
    }

    /**
     * @param string $filename
     */
    private function processDeleteHistory(string $filename)
    {
        $context = \Context::getContext();
        $safeFilename = basename($filename);

        if ($safeFilename !== $filename || strpos($filename, '..') !== false) {
            $context->controller->errors[] = $this->l('Invalid filename.');
            return;
        }

        try {
            CacheManager::delete($safeFilename);
        } catch (\PrestaShopException $e) {
            $context->controller->errors[] = $this->l('An error occurred while deleting the backup.');
            return;
        }

        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }

    /**
     * @return string
     */
    private function renderHistoryList(): string
    {
        $history = $this->getHistoryData();

        if (empty($history)) {
            return '';
        }

        $helper = new \HelperList();
        $helper->simple_header = true;
        $helper->show_toolbar = false;
        $helper->no_link = true;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = \AdminController::$currentIndex . '&configure=seooptimizer';
        $helper->identifier = 'file';
        $helper->table = 'llms_history';
        $helper->id = 'llms_history';
        $helper->_default_pagination = 10;
        $helper->_pagination = [10, 20];
        $helper->listTotal = count($history);
        $helper->actions = [];
        $helper->bulk_actions = [];

        $helper->tpl_vars = [
            'link' => \Context::getContext()->link,
        ];

        $fieldsList = [
            'date' => [
                'title' => $this->l('Date'),
                'type' => 'text',
                'orderby' => false,
            ],
            'file' => [
                'title' => $this->l('File'),
                'type' => 'text',
                'orderby' => false,
            ],
            'size' => [
                'title' => $this->l('Size'),
                'type' => 'text',
                'orderby' => false,
                'align' => 'right',
            ],
            'actions_html' => [
                'title' => $this->l('Actions'),
                'type' => 'text',
                'orderby' => false,
                'search' => false,
                'align' => 'right',
                'callback_object' => self::class,
                'callback' => 'displayHistoryActions',
            ],
        ];

        foreach ($history as $i => &$row) {
            $row['id_llms_history'] = $i + 1;
            $row['actions_html'] = $row['file'];
        }
        unset($row);

        return $helper->generateList($history, $fieldsList);
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function displayHistoryActions(string $filename): string
    {
        $baseUrl = \AdminController::$currentIndex
            . '&configure=seooptimizer&token=' . \Tools::getAdminTokenLite('AdminModules');
        $urlFile = urlencode($filename);

        return '<a href="' . $baseUrl . '&restoreLlms=' . $urlFile . '" class="btn btn-default btn-xs"'
            . ' onclick="return confirm(\'Restore this backup?\')">'
            . '<i class="icon-undo"></i> Restore'
            . '</a> '
            . '<a href="' . $baseUrl . '&deleteLlmsHistory=' . $urlFile . '" class="btn btn-default btn-xs"'
            . ' onclick="return confirm(\'Delete this backup?\')">'
            . '<i class="icon-trash"></i>'
            . '</a>';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getHistoryData(): array
    {
        $cacheDir = _PS_ROOT_DIR_ . '/var/cache/seooptimizer/';
        $history = [];

        if (!is_dir($cacheDir)) {
            return $history;
        }

        $files = glob($cacheDir . 'llms_*.txt');
        if (!is_array($files)) {
            return $history;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $files = array_slice($files, 0, 20);

        foreach ($files as $file) {
            $filename = basename($file);
            $mtime = filemtime($file);
            $size = filesize($file);

            $history[] = [
                'file' => $filename,
                'date' => date('d/m/Y H:i:s', $mtime),
                'size' => $size > 1024 ? round($size / 1024, 1) . ' KB' : $size . ' B',
            ];
        }

        return $history;
    }

    /**
     * @return string
     */
    private function getLlmsTxtContent(): string
    {
        $path = _PS_ROOT_DIR_ . '/llms.txt';
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                return $content;
            }
        }

        return '';
    }

    /**
     * @param string $shopName
     * @param string $shopUrl
     * @return array<string, string>
     */
    private function getPresetsContent(string $shopName, string $shopUrl): array
    {
        return [
            'ecommerce' => '# ' . $shopName . '

> ' . $shopName . ' is an online store. This file describes the site structure for AI assistants and large language models.

## About
- [Homepage](' . $shopUrl . '): Main page of the shop
- [Contact](' . $shopUrl . '/nous-contacter): Contact page

## Products
- [All products](' . $shopUrl . '/2-home): Browse the full product catalog
- [New products](' . $shopUrl . '/nouveaux-produits): Recently added products
- [Best sellers](' . $shopUrl . '/meilleures-ventes): Most popular products
- [Promotions](' . $shopUrl . '/promotions): Current deals and discounts

## Categories
- [Product categories](' . $shopUrl . '/): Browse by category

## Legal
- [Terms and conditions](' . $shopUrl . '/content/3-terms-and-conditions-of-use): Terms of service
- [Legal notice](' . $shopUrl . '/content/2-legal-notice): Legal information
- [Sitemap](' . $shopUrl . '/sitemap): HTML sitemap

## Optional
- [Sitemap XML](' . $shopUrl . '/sitemap.xml): XML sitemap for crawlers',

            'minimal' => '# ' . $shopName . '

> ' . $shopName . ' is an online store.

## About
- [Homepage](' . $shopUrl . '): Main page

## Legal
- [Terms and conditions](' . $shopUrl . '/content/3-terms-and-conditions-of-use): Terms of service',

            'detailed' => '# ' . $shopName . '

> ' . $shopName . ' is an online store powered by PrestaShop. This file provides a comprehensive description of the site for AI assistants and large language models.

## About
- [Homepage](' . $shopUrl . '): Main page of the shop
- [Contact](' . $shopUrl . '/nous-contacter): Contact information and form
- [Stores](' . $shopUrl . '/magasins): Physical store locations

## Products
- [All products](' . $shopUrl . '/2-home): Full product catalog
- [New products](' . $shopUrl . '/nouveaux-produits): Recently added items
- [Best sellers](' . $shopUrl . '/meilleures-ventes): Most popular products
- [Promotions](' . $shopUrl . '/promotions): Current sales and discounts
- [Price drop](' . $shopUrl . '/prix-reduits): Price reductions

## Categories
- [Product categories](' . $shopUrl . '/): Browse products by category

## Customer Info
- [Delivery](' . $shopUrl . '/content/1-delivery): Shipping information
- [Terms and conditions](' . $shopUrl . '/content/3-terms-and-conditions-of-use): Terms of service
- [Legal notice](' . $shopUrl . '/content/2-legal-notice): Legal information
- [Secure payment](' . $shopUrl . '/content/5-secure-payment): Payment security info

## Brands
- [Brands](' . $shopUrl . '/fabricants): All product brands

## Technical
- [Sitemap XML](' . $shopUrl . '/sitemap.xml): XML sitemap
- [Sitemap HTML](' . $shopUrl . '/sitemap): HTML sitemap

## Optional
- Preferred language: fr
- Contact email: ' . \Configuration::get('PS_SHOP_EMAIL') . '',

            'block' => '# ' . $shopName . '

> This website opts out of AI training and LLM indexing.

## Preferences
- AI Training: No
- AI Indexing: No
- Content Scraping: Not Allowed

All content on this website is copyrighted. Do not use for training or indexing purposes.',
        ];
    }
}
