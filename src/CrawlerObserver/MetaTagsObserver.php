<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $title = $this->extractTitle($content);
        $description = $this->extractMetaDescription($content);
        $issues = [];
        $pageSeverity = 'good';

        // Title checks
        if ($title === null) {
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
        if ($description === null) {
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
            'title' => $title,
            'title_length' => $title !== null ? mb_strlen($title) : 0,
            'description' => $description,
            'description_length' => $description !== null ? mb_strlen($description) : 0,
            'issues' => $issues,
            'page_severity' => $pageSeverity,
        ];
    }

    /**
     * @param string $content
     * @return string|null
     */
    private function extractTitle(string $content)
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $match)) {
            return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8'));
        }

        return null;
    }

    /**
     * @param string $content
     * @return string|null
     */
    private function extractMetaDescription(string $content)
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/is', $content, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
        }
        // Handle reversed attribute order: content before name
        if (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\'][^>]*>/is', $content, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
        }

        return null;
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
