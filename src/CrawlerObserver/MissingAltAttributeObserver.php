<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $issues = [];

        $bodyContent = $content;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }

        if (empty(trim($bodyContent))) {
            return;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $bodyContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');

        $totalImages = 0;
        $missingAlt = 0;
        $emptyAlt = 0;

        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // Skip tiny images (tracking pixels, spacers)
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');
            if (($width && (int) $width <= 1) || ($height && (int) $height <= 1)) {
                continue;
            }

            // Skip data URIs and inline SVGs
            if (strpos($src, 'data:') === 0) {
                continue;
            }

            $totalImages++;

            if (!$img->hasAttribute('alt')) {
                $missingAlt++;
                $issues[] = [
                    'type' => 'missing_alt',
                    'severity' => 'critical',
                    'message' => 'Missing alt attribute',
                    'src' => $src,
                ];
            } elseif (trim($img->getAttribute('alt')) === '') {
                $emptyAlt++;
                $issues[] = [
                    'type' => 'empty_alt',
                    'severity' => 'warning',
                    'message' => 'Empty alt attribute',
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
