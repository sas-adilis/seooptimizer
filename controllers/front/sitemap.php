<?php
/**
 * @author    Adilis <support@adilis.fr>
 * @copyright Adilis
 * @license   http://www.adilis.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\SitemapIndexer\SitemapIndexer;

class SeoOptimizerSitemapModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $page_type = Tools::getValue('type');
        if ($page_type) {
            $this->generatePageTypeSitemap();
        } else {
            $this->generateSitemapIndex();
        }
    }

    /**
     * @throws DOMException
     */
    private function generatePageTypeSitemap()
    {
        $start = microtime(true);
        $page_type = Tools::getValue('type');
        $page = (int) Tools::getValue('page', 1);

        if ($page_type) {
            $pages = SitemapIndexer::getPagesByType($page_type, $page);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;
            $url_set = $dom->createElement('urlset');
            $url_set->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $url_set->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
            $dom->appendChild($url_set);

            foreach ($pages as $page) {
                $url = $dom->createElement('url');
                $url->appendChild($dom->createElement('loc', $page['url']));
                $url->appendChild($dom->createElement('lastmod', $page['date_updated']));
                $url->appendChild($dom->createElement('changefreq', $page['frequency']));
                $url->appendChild($dom->createElement('priority', $page['priority']));
                $url_set->appendChild($url);

                if (isset($page['images']) && is_array($page['images'])) {
                    foreach ($page['images'] as $image) {
                        $image_url = $dom->createElement('image:image');
                        $image_url->appendChild($dom->createElement('image:loc', $image['url']));
                        if (!empty($image['name'])) {
                            $image_url->appendChild($dom->createElement('image:title', $this->encodeString($image['name'])));
                        }
                        if (!empty($image['caption'])) {
                            $image_url->appendChild($dom->createElement('image:caption', $this->encodeString($image['caption'])));
                        }
                        $url->appendChild($image_url);
                    }
                }
            }

            $time = microtime(true) - $start;
            $comment = $dom->createComment('Generated in ' . round($time, 2) . ' seconds');
            $dom->appendChild($comment);
            header('Content-Type: application/xml');
            echo $dom->saveXML();
        }

        exit;
    }

    private function encodeString($string): string
    {
        return htmlspecialchars(strip_tags($string));
    }

    /**
     * @throws DOMException
     */
    private function generateSitemapIndex()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $sitemap_index = $dom->createElement('sitemapindex');
        $sitemap_index->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $dom->appendChild($sitemap_index);
        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');

        foreach (Language::getLanguages() as $lang) {
            $pages_types = SitemapIndexer::getAllPagesTypes();
            foreach ($pages_types as $page_type) {
                $countPages = SitemapIndexer::getPagesCountByType($page_type);

                if ($countPages == 0) {
                    continue;
                }

                if ($per_page) {
                    $pages = ceil($countPages / $per_page);
                    for ($i = 1; $i <= $pages; $i++) {
                        $sitemap = $dom->createElement('sitemap');
                        $loc = $dom->createElement('loc', $this->context->link->getModuleLink('seooptimizer', 'sitemap', [
                            'type' => $page_type,
                            'page' => $i,
                            'lang' => $lang['iso_code']
                        ]));
                        $sitemap->appendChild($loc);
                        $sitemap_index->appendChild($sitemap);
                    }
                } else {
                    $sitemap = $dom->createElement('sitemap');
                    $loc = $dom->createElement('loc', $this->context->link->getModuleLink('seooptimizer', 'sitemap', [
                        'type' => $page_type,
                        'page' => 1,
                        'lang' => $lang['iso_code']
                    ]));
                    $sitemap->appendChild($loc);
                    $sitemap_index->appendChild($sitemap);
                }
            }
        }

        header('Content-Type: application/xml');
        echo $dom->saveXML();
        exit;
    }
}
