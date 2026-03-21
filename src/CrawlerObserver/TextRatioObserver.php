<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\TextNormalizer;

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
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $bodyHTML = $extractor->extractBodyHTML();

        if (empty(trim($bodyHTML))) {
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

        $htmlLength = strlen($bodyHTML);
        $bodyText = $extractor->extractBodyText();
        $text = html_entity_decode($bodyText, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));

        $textLength = strlen($text);
        $wordCount = TextNormalizer::countWords($text);
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
