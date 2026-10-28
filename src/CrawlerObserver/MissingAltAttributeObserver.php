<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\Message;

class MissingAltAttributeObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    public function getKey(): string
    {
        return 'missing_alt';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);

        // We need DOM-level access to distinguish missing alt from empty alt,
        // which extractImages() cannot provide (it returns '' for both cases).
        $dom = $extractor->getDOM();
        $imgNodes = $dom->getElementsByTagName('img');

        $issues = [];
        $totalImages = 0;
        $missingAlt = 0;
        $emptyAlt = 0;

        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');

            // Skip data URIs and inline SVGs
            if (strpos($src, 'data:') === 0) {
                continue;
            }

            // Skip empty src
            if (empty($src)) {
                continue;
            }

            // Skip tiny images (tracking pixels, spacers)
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');
            if (($width && (int) $width <= 1) || ($height && (int) $height <= 1)) {
                continue;
            }

            // Skip images explicitly marked as decorative
            $role = strtolower(trim($img->getAttribute('role')));
            if ($role === 'presentation' || $role === 'none' || strtolower(trim($img->getAttribute('aria-hidden'))) === 'true') {
                continue;
            }

            $totalImages++;

            if (!$img->hasAttribute('alt')) {
                $missingAlt++;
                $issues[] = [
                    'type' => 'missing_alt',
                    'severity' => 'critical',
                    'message' => Message::t('Missing alt attribute'),
                    'src' => $src,
                ];
            } elseif (trim($img->getAttribute('alt')) === '') {
                $emptyAlt++;
                $issues[] = [
                    'type' => 'empty_alt',
                    'severity' => 'info',
                    'message' => Message::t('Empty alt attribute (fine if the image is decorative)'),
                    'src' => $src,
                ];
            }
        }

        if (count($issues) > 0) {
            $this->results[$url] = [
                'issues' => $issues,
                'total_images' => $totalImages,
                'missing_alt' => $missingAlt,
                'empty_alt' => $emptyAlt,
            ];
        }
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
