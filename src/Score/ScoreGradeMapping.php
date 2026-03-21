<?php

namespace Adilis\SeoOptimizer\Score;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Centralized score-to-grade mapping.
 * Single source of truth for grade thresholds — used by both BO and FO.
 */
class ScoreGradeMapping
{
    /**
     * @param float $score 0-100
     * @return array{grade: string, color: string}
     */
    public static function fromScore(float $score): array
    {
        if ($score >= 95) {
            return ['grade' => 'A+', 'color' => 'green'];
        }
        if ($score >= 85) {
            return ['grade' => 'A', 'color' => 'green'];
        }
        if ($score >= 70) {
            return ['grade' => 'B', 'color' => 'green'];
        }
        if ($score >= 55) {
            return ['grade' => 'C', 'color' => 'orange'];
        }
        if ($score >= 40) {
            return ['grade' => 'D', 'color' => 'orange'];
        }
        if ($score >= 25) {
            return ['grade' => 'E', 'color' => 'red'];
        }
        return ['grade' => 'F', 'color' => 'red'];
    }
}
