<?php

namespace Adilis\SeoOptimizer\Score;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditRegistry;
use Adilis\SeoOptimizer\Audit\ScoredAuditInterface;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;

class SeoScoreCalculator
{
    /** @var AuditInterface[] */
    private $audits;

    public function __construct()
    {
        $this->audits = AuditRegistry::getAll();
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

            // Per-page score
            $auditPageScores = $this->computeAuditPageScores($audit);
            foreach ($auditPageScores as $url => $pageScore) {
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
     * Score of one page from the rows (issues) that each audit found on it: per audit, 100
     * minus the penalty of each issue, then the average weighted by the audit weights.
     * An audit without rows entry did not apply to the page and is left out.
     *
     * @param string $url
     * @param array<string, array> $rowsByAudit Audit key => rows of the page
     * @return array{score: float, audits: array<string, array{score: float, critical: int, warning: int, info: int}>}
     */
    public function summarizePage(string $url, array $rowsByAudit): array
    {
        $weightedSum = 0;
        $totalWeight = 0;
        $audits = [];

        foreach ($this->audits as $audit) {
            $key = $audit->getKey();
            if (!isset($rowsByAudit[$key])) {
                continue;
            }

            $impact = $audit->getScoreImpact();
            $penalty = 0;
            $counts = ['critical' => 0, 'warning' => 0, 'info' => 0];
            foreach ($rowsByAudit[$key] as $row) {
                $severity = isset($row['severity']) ? $row['severity'] : '';
                $penalty += isset($impact[$severity]) ? $impact[$severity] : 0;
                if (isset($counts[$severity])) {
                    ++$counts[$severity];
                }
            }

            $score = [$url => max(0, 100 - $penalty)];
            if ($audit instanceof ScoredAuditInterface) {
                $score = $audit->overridePageScores($score);
            }

            $audits[$key] = [
                'score' => (float) $score[$url],
                'critical' => $counts['critical'],
                'warning' => $counts['warning'],
                'info' => $counts['info'],
            ];
            $weightedSum += $score[$url] * $audit->getScoreWeight();
            $totalWeight += $audit->getScoreWeight();
        }

        return [
            'score' => $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : 100.0,
            'audits' => $audits,
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

        $scores = $this->computeAuditPageScores($audit);

        $avg = count($scores) > 0 ? array_sum($scores) / count($scores) : 100;

        return [
            'score' => round($avg, 1),
            'grade' => self::scoreToGrade($avg),
            'grade_color' => self::gradeToColor(self::scoreToGrade($avg)),
        ];
    }

    /**
     * Score of every page crawled by an audit: 100 minus the penalty of each
     * issue found on the page (floor 0).
     *
     * @param AuditInterface $audit
     * @return array<string, int|float> URL => score
     */
    private function computeAuditPageScores(AuditInterface $audit): array
    {
        $key = $audit->getKey();
        $run = AuditRunStorage::get($key);
        $impact = $audit->getScoreImpact();

        $auditUrls = [];
        if ($run && isset($run['urls'])) {
            foreach ($run['urls'] as $entry) {
                // Pages this audit could not analyze (non-indexable, unreachable) are left out
                if (is_array($entry) && isset($entry['skip']) && in_array($key, $entry['skip'], true)) {
                    continue;
                }
                $url = is_array($entry) ? ($entry['url'] ?? '') : (string) $entry;
                if ($url !== '') {
                    $auditUrls[] = $url;
                }
            }
            $auditUrls = array_unique($auditUrls);
        }

        $issuesByUrl = [];
        foreach (AuditResultStorage::getByAuditKey($key) as $row) {
            $url = $row['url'] ?? '';
            if ($url === '') {
                continue;
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
            $scores[$url] = max(0, 100 - $penalty);
        }

        if ($audit instanceof ScoredAuditInterface) {
            $scores = $audit->overridePageScores($scores);
        }

        return $scores;
    }

    public static function scoreToGrade(float $score): string
    {
        $data = ScoreGradeMapping::fromScore($score);
        return $data['grade'];
    }

    public static function gradeToColor(string $grade): string
    {
        $map = [
            'A+' => 'excellent', 'A' => 'good', 'B' => 'fair',
            'C' => 'warning', 'D' => 'poor', 'E' => 'critical', 'F' => 'critical',
        ];
        return isset($map[$grade]) ? $map[$grade] : 'gray';
    }
}
