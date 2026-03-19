<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TextRatioObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var int */
    private $thresholdLow;

    /** @var int */
    private $thresholdGood;

    /** @var int */
    private $goodCount = 0;

    /** @var int */
    private $mediumCount = 0;

    /** @var int */
    private $lowCount = 0;

    public function __construct()
    {
        $this->thresholdLow = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_LOW') ?: 100;
        $this->thresholdGood = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_GOOD') ?: 300;
    }

    public function getKey(): string
    {
        return 'text_ratio';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $bodyContent = $content;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }

        if (empty(trim($bodyContent))) {
            $this->results[] = [
                'url' => $url,
                'word_count' => 0,
                'text_length' => 0,
                'html_length' => 0,
                'text_ratio' => 0,
                'severity' => 'critical',
            ];
            $this->lowCount++;
            return;
        }

        $htmlLength = strlen($bodyContent);

        // Remove scripts, styles, nav, header, footer to get main content text
        $cleanContent = $bodyContent;
        $cleanContent = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $cleanContent);
        $cleanContent = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $cleanContent);
        $cleanContent = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $cleanContent);
        $cleanContent = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $cleanContent);
        $cleanContent = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $cleanContent);

        // Strip tags and decode entities
        $text = strip_tags($cleanContent);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $textLength = strlen($text);
        $wordCount = $text !== '' ? count(preg_split('/\s+/', $text)) : 0;
        $textRatio = $htmlLength > 0 ? round(($textLength / $htmlLength) * 100, 1) : 0;

        if ($wordCount < $this->thresholdLow) {
            $severity = 'critical';
            $this->lowCount++;
        } elseif ($wordCount < $this->thresholdGood) {
            $severity = 'warning';
            $this->mediumCount++;
        } else {
            $severity = 'good';
            $this->goodCount++;
        }

        $this->results[] = [
            'url' => $url,
            'word_count' => $wordCount,
            'text_length' => $textLength,
            'html_length' => $htmlLength,
            'text_ratio' => $textRatio,
            'severity' => $severity,
        ];
    }

    /**
     * @return int
     */
    public function getGoodCount(): int
    {
        return $this->goodCount;
    }

    /**
     * @return int
     */
    public function getMediumCount(): int
    {
        return $this->mediumCount;
    }

    /**
     * @return int
     */
    public function getLowCount(): int
    {
        return $this->lowCount;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
