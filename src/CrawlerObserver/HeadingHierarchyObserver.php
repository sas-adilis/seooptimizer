<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;

class HeadingHierarchyObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    const MIN_H1_LENGTH = 10;
    const MAX_H1_LENGTH = 70;
    const MIN_HEADING_LENGTH = 3;
    const MAX_HEADING_LENGTH = 100;

    /** @var array */
    private $results = [];

    public function getKey(): string
    {
        return 'heading_hierarchy';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $rawHeadings = $extractor->extractHeadings();
        $headings = $this->groupHeadings($rawHeadings);
        $issues = [];

        // Check H1
        $h1Count = count($headings[1]);
        if ($h1Count === 0) {
            $issues[] = [
                'type' => 'missing_h1',
                'severity' => 'critical',
                'message' => 'No H1 tag found on this page',
            ];
        } elseif ($h1Count > 1) {
            $issues[] = [
                'type' => 'multiple_h1',
                'severity' => 'critical',
                'message' => sprintf('%d H1 tags found (should be exactly 1)', $h1Count),
                'details' => array_column($headings[1], 'text'),
            ];
        }

        // Check H1 length
        foreach ($headings[1] as $h1) {
            $len = mb_strlen($h1['text']);
            if ($len < self::MIN_H1_LENGTH) {
                $issues[] = [
                    'type' => 'h1_too_short',
                    'severity' => 'warning',
                    'message' => sprintf('H1 too short (%d chars, min %d): "%s"', $len, self::MIN_H1_LENGTH, $h1['text']),
                ];
            } elseif ($len > self::MAX_H1_LENGTH) {
                $issues[] = [
                    'type' => 'h1_too_long',
                    'severity' => 'warning',
                    'message' => sprintf('H1 too long (%d chars, max %d): "%s"', $len, self::MAX_H1_LENGTH, $h1['text']),
                ];
            }
        }

        // Check heading lengths for H2-H6
        for ($level = 2; $level <= 6; $level++) {
            foreach ($headings[$level] as $heading) {
                $len = mb_strlen($heading['text']);
                if ($len > 0 && $len < self::MIN_HEADING_LENGTH) {
                    $issues[] = [
                        'type' => 'heading_too_short',
                        'severity' => 'info',
                        'message' => sprintf('H%d too short (%d chars): "%s"', $level, $len, $heading['text']),
                    ];
                } elseif ($len > self::MAX_HEADING_LENGTH) {
                    $issues[] = [
                        'type' => 'heading_too_long',
                        'severity' => 'info',
                        'message' => sprintf('H%d too long (%d chars): "%s"', $level, $len, $heading['text']),
                    ];
                }
                if (empty(trim($heading['text']))) {
                    $issues[] = [
                        'type' => 'empty_heading',
                        'severity' => 'warning',
                        'message' => sprintf('Empty H%d tag found', $level),
                    ];
                }
            }
        }

        // Check hierarchy (no skipped levels)
        $hierarchyIssues = $this->checkHierarchy($headings);
        $issues = array_merge($issues, $hierarchyIssues);

        // Check duplicate headings at same level
        $duplicateIssues = $this->checkDuplicates($headings);
        $issues = array_merge($issues, $duplicateIssues);

        if (count($issues) > 0 || count($headings[1]) > 0) {
            $this->results[$url] = [
                'headings' => $headings,
                'issues' => $issues,
                'counts' => $this->getCounts($headings),
            ];
        }
    }

    /**
     * Group flat headings array into per-level buckets with an 'ordered' key.
     *
     * @param array<int, array{level: int, text: string}> $rawHeadings
     * @return array
     */
    private function groupHeadings(array $rawHeadings): array
    {
        $headings = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
        $orderedHeadings = [];

        foreach ($rawHeadings as $entry) {
            $level = $entry['level'];
            $headings[$level][] = $entry;
            $orderedHeadings[] = $entry;
        }

        $headings['ordered'] = $orderedHeadings;

        return $headings;
    }

    /**
     * @param array $headings
     * @return array
     */
    private function checkHierarchy(array $headings): array
    {
        $issues = [];
        $ordered = $headings['ordered'];

        if (empty($ordered)) {
            return $issues;
        }

        // First heading should be H1
        if ($ordered[0]['level'] !== 1) {
            $issues[] = [
                'type' => 'first_heading_not_h1',
                'severity' => 'warning',
                'message' => sprintf('First heading is H%d instead of H1', $ordered[0]['level']),
            ];
        }

        // Check for skipped levels (e.g. H1 -> H3 without H2)
        $previousLevel = 0;
        foreach ($ordered as $heading) {
            $level = $heading['level'];
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $skipped = [];
                for ($i = $previousLevel + 1; $i < $level; $i++) {
                    $skipped[] = 'H' . $i;
                }
                $issues[] = [
                    'type' => 'skipped_level',
                    'severity' => 'warning',
                    'message' => sprintf(
                        'Skipped heading level: H%d to H%d (missing %s)',
                        $previousLevel,
                        $level,
                        implode(', ', $skipped)
                    ),
                ];
            }
            $previousLevel = $level;
        }

        return $issues;
    }

    /**
     * @param array $headings
     * @return array
     */
    private function checkDuplicates(array $headings): array
    {
        $issues = [];

        for ($level = 1; $level <= 6; $level++) {
            $texts = array_map(function ($h) {
                return mb_strtolower(trim($h['text']));
            }, $headings[$level]);

            $counts = array_count_values(array_filter($texts));
            foreach ($counts as $text => $count) {
                if ($count > 1 && $level <= 3) {
                    $issues[] = [
                        'type' => 'duplicate_heading',
                        'severity' => $level === 1 ? 'critical' : 'info',
                        'message' => sprintf('Duplicate H%d (%dx): "%s"', $level, $count, $text),
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * @param array $headings
     * @return array
     */
    private function getCounts(array $headings): array
    {
        $counts = [];
        for ($level = 1; $level <= 6; $level++) {
            $counts['h' . $level] = count($headings[$level]);
        }

        return $counts;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
