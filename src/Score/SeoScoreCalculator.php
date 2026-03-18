<?php

namespace Adilis\SeoOptimizer\Score;

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditBrokenLinks;
use Adilis\SeoOptimizer\Audit\AuditHeadingHierarchy;
use Adilis\SeoOptimizer\Audit\AuditMissingAlt;
use Adilis\SeoOptimizer\Audit\AuditPageLoadTime;
use Adilis\SeoOptimizer\Audit\AuditPageWeight;
use Adilis\SeoOptimizer\Audit\AuditUnsecuredLinks;
use Adilis\SeoOptimizer\Audit\AuditMetaTags;
use Adilis\SeoOptimizer\Audit\AuditInternalLinks;
use Adilis\SeoOptimizer\Audit\AuditTextRatio;
use Adilis\SeoOptimizer\CacheManager;

class SeoScoreCalculator
{
    /** @var AuditInterface[] */
    private $audits;

    /** @var array<string, array|null> */
    private $auditStates = [];

    public function __construct()
    {
        $this->audits = [
            new AuditHeadingHierarchy(),
            new AuditMissingAlt(),
            new AuditBrokenLinks(),
            new AuditPageLoadTime(),
            new AuditPageWeight(),
            new AuditUnsecuredLinks(),
            new AuditMetaTags(),
            new AuditInternalLinks(),
            new AuditTextRatio(),
        ];

        foreach ($this->audits as $audit) {
            $this->auditStates[$audit->getKey()] = CacheManager::get('audit_' . $audit->getKey());
        }
    }

    /**
     * Compute full scoring data.
     *
     * @return array{global: array, audits: array<string, array>, pages: array<string, array>, has_data: bool}
     */
    public function compute(): array
    {
        if (!$this->hasAnyData()) {
            return [
                'global' => ['score' => 0, 'grade' => '-', 'grade_color' => 'gray'],
                'audits' => [],
                'pages' => [],
                'has_data' => false,
            ];
        }

        $allUrls = $this->collectAllUrls();
        $auditScores = [];
        $pageScores = [];

        foreach ($this->audits as $audit) {
            $key = $audit->getKey();
            $state = $this->auditStates[$key];

            if (!$state || !isset($state['status']) || $state['status'] !== 'complete') {
                continue;
            }

            $results = $state['results'] ?? [];
            $impact = $audit->getScoreImpact();

            // Group results by URL for this audit
            $issuesByUrl = [];
            foreach ($results as $row) {
                $url = $row['url'] ?? '';
                if ($url === '') {
                    continue;
                }
                if (!isset($issuesByUrl[$url])) {
                    $issuesByUrl[$url] = [];
                }
                $issuesByUrl[$url][] = $row;
            }

            // Compute per-page score for this audit
            $auditPageScores = [];
            $auditUrls = $this->getAuditUrls($state);

            foreach ($auditUrls as $url) {
                $penalty = 0;
                if (isset($issuesByUrl[$url])) {
                    foreach ($issuesByUrl[$url] as $issue) {
                        $severity = $issue['severity'] ?? '';
                        if (isset($impact[$severity])) {
                            $penalty += $impact[$severity];
                        }
                    }
                }

                $pageScore = max(0, 100 - $penalty);
                $auditPageScores[$url] = $pageScore;

                // Accumulate for per-page global scores
                if (!isset($pageScores[$url])) {
                    $pageScores[$url] = [
                        'url' => $url,
                        'audits' => [],
                        'weighted_sum' => 0,
                        'weight_total' => 0,
                    ];
                }
                $pageScores[$url]['audits'][$key] = $pageScore;
                $pageScores[$url]['weighted_sum'] += $pageScore * $audit->getScoreWeight();
                $pageScores[$url]['weight_total'] += $audit->getScoreWeight();
            }

            // Per-audit global score = average of all page scores for this audit
            $auditAvg = count($auditPageScores) > 0
                ? array_sum($auditPageScores) / count($auditPageScores)
                : 100;

            $auditScores[$key] = [
                'score' => round($auditAvg, 1),
                'grade' => self::scoreToGrade($auditAvg),
                'grade_color' => self::gradeToColor(self::scoreToGrade($auditAvg)),
                'weight' => $audit->getScoreWeight(),
                'title' => $audit->getTitle(),
                'icon' => $audit->getIcon(),
                'pages_count' => count($auditPageScores),
            ];
        }

        // Finalize per-page scores
        $pagesResult = [];
        foreach ($pageScores as $url => $data) {
            $score = $data['weight_total'] > 0
                ? $data['weighted_sum'] / $data['weight_total']
                : 100;
            $pagesResult[$url] = [
                'url' => $url,
                'score' => round($score, 1),
                'grade' => self::scoreToGrade($score),
                'grade_color' => self::gradeToColor(self::scoreToGrade($score)),
                'audits' => $data['audits'],
            ];
        }

        // Sort pages by score ascending (worst first)
        uasort($pagesResult, function ($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        // Global score = weighted average of per-audit scores
        $globalWeightedSum = 0;
        $globalWeightTotal = 0;
        foreach ($auditScores as $auditData) {
            $globalWeightedSum += $auditData['score'] * $auditData['weight'];
            $globalWeightTotal += $auditData['weight'];
        }

        $globalScore = $globalWeightTotal > 0
            ? $globalWeightedSum / $globalWeightTotal
            : 0;

        return [
            'global' => [
                'score' => round($globalScore, 1),
                'grade' => self::scoreToGrade($globalScore),
                'grade_color' => self::gradeToColor(self::scoreToGrade($globalScore)),
            ],
            'audits' => $auditScores,
            'pages' => $pagesResult,
            'has_data' => true,
        ];
    }

    /**
     * Compute score for a single audit (lightweight, no page-level data).
     *
     * @param AuditInterface $audit
     * @return array{score: float, grade: string, grade_color: string}
     */
    public function computeForAudit(AuditInterface $audit): array
    {
        $state = $this->auditStates[$audit->getKey()] ?? null;

        if (!$state || !isset($state['status']) || $state['status'] !== 'complete') {
            return ['score' => 0, 'grade' => '-', 'grade_color' => 'gray'];
        }

        $results = $state['results'] ?? [];
        $impact = $audit->getScoreImpact();
        $auditUrls = $this->getAuditUrls($state);

        $issuesByUrl = [];
        foreach ($results as $row) {
            $url = $row['url'] ?? '';
            if ($url === '') {
                continue;
            }
            if (!isset($issuesByUrl[$url])) {
                $issuesByUrl[$url] = [];
            }
            $issuesByUrl[$url][] = $row;
        }

        $scores = [];
        foreach ($auditUrls as $url) {
            $penalty = 0;
            if (isset($issuesByUrl[$url])) {
                foreach ($issuesByUrl[$url] as $issue) {
                    $severity = $issue['severity'] ?? '';
                    if (isset($impact[$severity])) {
                        $penalty += $impact[$severity];
                    }
                }
            }
            $scores[] = max(0, 100 - $penalty);
        }

        $avg = count($scores) > 0 ? array_sum($scores) / count($scores) : 100;

        return [
            'score' => round($avg, 1),
            'grade' => self::scoreToGrade($avg),
            'grade_color' => self::gradeToColor(self::scoreToGrade($avg)),
        ];
    }

    /**
     * @param float $score
     * @return string
     */
    public static function scoreToGrade(float $score): string
    {
        if ($score >= 95) {
            return 'A+';
        }
        if ($score >= 85) {
            return 'A';
        }
        if ($score >= 70) {
            return 'B';
        }
        if ($score >= 50) {
            return 'C';
        }
        if ($score >= 30) {
            return 'D';
        }

        return 'F';
    }

    /**
     * @param string $grade
     * @return string
     */
    public static function gradeToColor(string $grade): string
    {
        switch ($grade) {
            case 'A+':
                return 'excellent';
            case 'A':
                return 'good';
            case 'B':
                return 'fair';
            case 'C':
                return 'warning';
            case 'D':
                return 'poor';
            case 'F':
                return 'critical';
            default:
                return 'gray';
        }
    }

    /**
     * @return bool
     */
    private function hasAnyData(): bool
    {
        foreach ($this->auditStates as $state) {
            if ($state && isset($state['status']) && $state['status'] === 'complete') {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect all unique URLs across all audit states.
     *
     * @return string[]
     */
    private function collectAllUrls(): array
    {
        $urls = [];
        foreach ($this->auditStates as $state) {
            if (!$state || !isset($state['urls'])) {
                continue;
            }
            foreach ($state['urls'] as $entry) {
                if (is_array($entry) && isset($entry['url'])) {
                    $urls[$entry['url']] = true;
                } elseif (is_string($entry)) {
                    $urls[$entry] = true;
                }
            }
        }

        return array_keys($urls);
    }

    /**
     * Get list of URLs crawled in a specific audit.
     *
     * @param array $state
     * @return string[]
     */
    private function getAuditUrls(array $state): array
    {
        $urls = [];
        if (isset($state['urls'])) {
            foreach ($state['urls'] as $entry) {
                if (is_array($entry) && isset($entry['url'])) {
                    $urls[] = $entry['url'];
                } elseif (is_string($entry)) {
                    $urls[] = $entry;
                }
            }
        }

        return array_unique($urls);
    }
}
