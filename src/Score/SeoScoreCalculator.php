<?php

namespace Adilis\SeoOptimizer\Score;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditBrokenLinks;
use Adilis\SeoOptimizer\Audit\AuditHeadingHierarchy;
use Adilis\SeoOptimizer\Audit\AuditInternalLinks;
use Adilis\SeoOptimizer\Audit\AuditKeywordCheck;
use Adilis\SeoOptimizer\Audit\AuditMetaTags;
use Adilis\SeoOptimizer\Audit\AuditMissingAlt;
use Adilis\SeoOptimizer\Audit\AuditRedirectedLinks;
use Adilis\SeoOptimizer\Audit\AuditPageLoadTime;
use Adilis\SeoOptimizer\Audit\AuditPageWeight;
use Adilis\SeoOptimizer\Audit\AuditTextRatio;
use Adilis\SeoOptimizer\Audit\AuditUnsecuredLinks;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;

class SeoScoreCalculator
{
    /** @var AuditInterface[] */
    private $audits;

    public function __construct()
    {
        $this->audits = [
            new AuditHeadingHierarchy(),
            new AuditMissingAlt(),
            new AuditBrokenLinks(),
            new AuditRedirectedLinks(),
            new AuditPageLoadTime(),
            new AuditPageWeight(),
            new AuditUnsecuredLinks(),
            new AuditMetaTags(),
            new AuditInternalLinks(),
            new AuditTextRatio(),
            new AuditKeywordCheck(),
        ];
    }

    /**
     * @return array
     */
    public function compute(): array
    {
        if (!AuditRunStorage::hasAnyComplete()) {
            return [
                'global' => ['score' => 0, 'grade' => '-', 'grade_color' => 'gray'],
                'audits' => [],
                'pages' => [],
                'has_data' => false,
            ];
        }

        $auditScores = [];
        $pageScores = [];

        foreach ($this->audits as $audit) {
            $key = $audit->getKey();

            if (!AuditRunStorage::isComplete($key)) {
                continue;
            }

            $run = AuditRunStorage::get($key);
            $results = AuditResultStorage::getByAuditKey($key);
            $impact = $audit->getScoreImpact();

            // Get all URLs for this audit
            $auditUrls = [];
            if ($run && isset($run['urls'])) {
                foreach ($run['urls'] as $entry) {
                    $url = is_array($entry) ? ($entry['url'] ?? '') : (string) $entry;
                    if ($url !== '') {
                        $auditUrls[] = $url;
                    }
                }
                $auditUrls = array_unique($auditUrls);
            }

            // Group results by URL
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

            // Per-page score
            $auditPageScores = [];
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

        uasort($pagesResult, function ($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        // Global score
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
     * @param AuditInterface $audit
     * @return array
     */
    public function computeForAudit(AuditInterface $audit): array
    {
        $key = $audit->getKey();

        if (!AuditRunStorage::isComplete($key)) {
            return ['score' => 0, 'grade' => '-', 'grade_color' => 'gray'];
        }

        $run = AuditRunStorage::get($key);
        $results = AuditResultStorage::getByAuditKey($key);
        $impact = $audit->getScoreImpact();

        $auditUrls = [];
        if ($run && isset($run['urls'])) {
            foreach ($run['urls'] as $entry) {
                $url = is_array($entry) ? ($entry['url'] ?? '') : (string) $entry;
                if ($url !== '') {
                    $auditUrls[] = $url;
                }
            }
            $auditUrls = array_unique($auditUrls);
        }

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

    public static function scoreToGrade(float $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 30) return 'D';
        return 'F';
    }

    public static function gradeToColor(string $grade): string
    {
        $map = ['A+' => 'excellent', 'A' => 'good', 'B' => 'fair', 'C' => 'warning', 'D' => 'poor', 'F' => 'critical'];
        return isset($map[$grade]) ? $map[$grade] : 'gray';
    }
}
