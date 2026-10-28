<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\Message;

/**
 * Social and technical tags of a page: Open Graph, Twitter card, language, viewport.
 *
 * Every tag gives a check (good, info or warning): the audit keeps the problems,
 * the front-office panel displays them all.
 */
class SocialTagsObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array<string, array{checks: array}> */
    private $results = [];

    public function getKey(): string
    {
        return 'social_tags';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, ?HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $checks = [];

        $og = $extractor->extractPrefixedMetas('property', 'og:');
        if (empty($og)) {
            $checks[] = $this->check('og_missing', 'warning', Message::t('Open Graph tags missing'), Message::t('The page is shared without title or image preview.'));
        } else {
            if (!isset($og['og:image'])) {
                $checks[] = $this->check('og_image_missing', 'warning', Message::t('og:image missing'), Message::t('The shared link has no image preview.'));
            }
            foreach (['og:title', 'og:description'] as $tag) {
                if (!isset($og[$tag])) {
                    $checks[] = $this->check('og_tag_missing', 'info', Message::t('%s missing', $tag));
                }
            }
            if (isset($og['og:image'], $og['og:title'], $og['og:description'])) {
                $checks[] = $this->check('og_ok', 'good', Message::t('Open Graph tags complete'));
            }
        }

        if (empty($extractor->extractPrefixedMetas('name', 'twitter:'))) {
            $checks[] = $this->check('twitter_missing', 'info', Message::t('Twitter card missing'), Message::t('X falls back on Open Graph tags when they exist.'));
        } else {
            $checks[] = $this->check('twitter_ok', 'good', Message::t('Twitter card defined'));
        }

        $lang = $extractor->extractHtmlLang();
        $checks[] = $lang === ''
            ? $this->check('lang_missing', 'warning', Message::t('Language of the page missing'), Message::t('Add a lang attribute to the html tag.'))
            : $this->check('lang_ok', 'good', Message::t('Language of the page defined'), $lang);

        $checks[] = $extractor->extractViewport() === ''
            ? $this->check('viewport_missing', 'warning', Message::t('Viewport tag missing'), Message::t('Required for mobile-first indexing.'))
            : $this->check('viewport_ok', 'good', Message::t('Viewport tag defined'));

        $this->results[$url] = ['checks' => $checks];
    }

    /**
     * @return array<string, array{checks: array}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param string $type
     * @param string $severity
     * @param string $message
     * @param string $detail
     * @return array<string, string>
     */
    private function check(string $type, string $severity, string $message, string $detail = ''): array
    {
        return ['type' => $type, 'severity' => $severity, 'message' => $message, 'detail' => $detail];
    }
}
