<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;

class MetaTagsObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var int */
    private $titleMinLength;

    /** @var int */
    private $titleMaxLength;

    /** @var int */
    private $descMinLength;

    /** @var int */
    private $descMaxLength;

    /** @var int */
    private $goodCount = 0;

    /** @var int */
    private $warningCount = 0;

    /** @var int */
    private $criticalCount = 0;

    public function __construct()
    {
        $this->titleMinLength = (int) \Configuration::get('SEOO_TITLE_MIN_LENGTH') ?: 50;
        $this->titleMaxLength = (int) \Configuration::get('SEOO_TITLE_MAX_LENGTH') ?: 70;
        $this->descMinLength = (int) \Configuration::get('SEOO_META_TITLE_MIN_LENGTH') ?: 140;
        $this->descMaxLength = (int) \Configuration::get('SEOO_META_TITLE_MAX_LENGTH') ?: 170;
    }

    public function getKey(): string
    {
        return 'meta_tags';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);

        $title = $extractor->extractTitle();
        $description = $extractor->extractMetaDescription();
        $issues = [];
        $pageSeverity = 'good';

        // HTMLExtractor returns empty string when tag is missing
        // We treat empty string as "missing" for backwards compatibility
        $titlePresent = $title !== '';
        $descPresent = $description !== '';

        // Title checks
        if (!$titlePresent) {
            $issues[] = [
                'type' => 'missing_title',
                'severity' => 'critical',
                'field' => 'title',
                'message' => 'Missing <title> tag',
                'length' => 0,
            ];
            $pageSeverity = 'critical';
        } else {
            $titleLen = mb_strlen($title);

            if ($titleLen === 0) {
                $issues[] = [
                    'type' => 'empty_title',
                    'severity' => 'critical',
                    'field' => 'title',
                    'message' => 'Empty <title> tag',
                    'length' => 0,
                ];
                $pageSeverity = 'critical';
            } elseif ($titleLen < $this->titleMinLength) {
                $issues[] = [
                    'type' => 'title_too_short',
                    'severity' => 'warning',
                    'field' => 'title',
                    'message' => sprintf(
                        'Title too short (%d chars, min %d)',
                        $titleLen,
                        $this->titleMinLength
                    ),
                    'length' => $titleLen,
                ];
                if ($pageSeverity === 'good') {
                    $pageSeverity = 'warning';
                }
            } elseif ($titleLen > $this->titleMaxLength) {
                $issues[] = [
                    'type' => 'title_too_long',
                    'severity' => 'warning',
                    'field' => 'title',
                    'message' => sprintf(
                        'Title too long (%d chars, max %d)',
                        $titleLen,
                        $this->titleMaxLength
                    ),
                    'length' => $titleLen,
                ];
                if ($pageSeverity === 'good') {
                    $pageSeverity = 'warning';
                }
            }
        }

        // Meta description checks
        if (!$descPresent) {
            $issues[] = [
                'type' => 'missing_description',
                'severity' => 'critical',
                'field' => 'description',
                'message' => 'Missing meta description',
                'length' => 0,
            ];
            $pageSeverity = 'critical';
        } else {
            $descLen = mb_strlen($description);

            if ($descLen === 0) {
                $issues[] = [
                    'type' => 'empty_description',
                    'severity' => 'critical',
                    'field' => 'description',
                    'message' => 'Empty meta description',
                    'length' => 0,
                ];
                $pageSeverity = 'critical';
            } elseif ($descLen < $this->descMinLength) {
                $issues[] = [
                    'type' => 'description_too_short',
                    'severity' => 'warning',
                    'field' => 'description',
                    'message' => sprintf(
                        'Meta description too short (%d chars, min %d)',
                        $descLen,
                        $this->descMinLength
                    ),
                    'length' => $descLen,
                ];
                if ($pageSeverity === 'good') {
                    $pageSeverity = 'warning';
                }
            } elseif ($descLen > $this->descMaxLength) {
                $issues[] = [
                    'type' => 'description_too_long',
                    'severity' => 'warning',
                    'field' => 'description',
                    'message' => sprintf(
                        'Meta description too long (%d chars, max %d)',
                        $descLen,
                        $this->descMaxLength
                    ),
                    'length' => $descLen,
                ];
                if ($pageSeverity === 'good') {
                    $pageSeverity = 'warning';
                }
            }
        }

        // Track counts
        switch ($pageSeverity) {
            case 'critical':
                $this->criticalCount++;
                break;
            case 'warning':
                $this->warningCount++;
                break;
            default:
                $this->goodCount++;
                break;
        }

        $this->results[$url] = [
            'title' => $titlePresent ? $title : null,
            'title_length' => $titlePresent ? mb_strlen($title) : 0,
            'description' => $descPresent ? $description : null,
            'description_length' => $descPresent ? mb_strlen($description) : 0,
            'issues' => $issues,
            'page_severity' => $pageSeverity,
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
    public function getWarningCount(): int
    {
        return $this->warningCount;
    }

    /**
     * @return int
     */
    public function getCriticalCount(): int
    {
        return $this->criticalCount;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
