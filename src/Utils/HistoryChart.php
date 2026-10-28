<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Line chart of a score history, rendered as an inline SVG: the same chart is used by the
 * dashboard, the audits and the panels (back office and front office), without JavaScript.
 */
class HistoryChart
{
    const WIDTH = 320;
    const HEIGHT = 120;

    /** Plot area */
    const LEFT = 26;
    const RIGHT = 10;
    const TOP = 10;
    const BOTTOM = 24;

    /**
     * @param array<int, array{date: string, score: float}> $series Oldest first
     * @return string SVG, empty when there are not two dates to draw
     */
    public static function render(array $series): string
    {
        if (count($series) < 2) {
            return '';
        }

        $first = strtotime($series[0]['date']);
        $span = max(1, strtotime($series[count($series) - 1]['date']) - $first);
        $plotWidth = self::WIDTH - self::LEFT - self::RIGHT;
        $plotHeight = self::HEIGHT - self::TOP - self::BOTTOM;

        $svg = '<svg class="seoo-history__chart" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . self::WIDTH . ' ' . self::HEIGHT
            . '" width="100%" role="img" font-size="9" fill="#6b7280">';

        // Grid: 0, 50 and 100
        foreach ([0, 50, 100] as $level) {
            $y = self::TOP + $plotHeight * (1 - $level / 100);
            $svg .= '<line x1="' . self::LEFT . '" y1="' . $y . '" x2="' . (self::WIDTH - self::RIGHT) . '" y2="' . $y . '" stroke="#e5e7eb" stroke-width="1"/>'
                . '<text x="' . (self::LEFT - 4) . '" y="' . ($y + 3) . '" text-anchor="end">' . $level . '</text>';
        }

        $points = [];
        $dots = '';
        foreach ($series as $point) {
            $x = round(self::LEFT + $plotWidth * (strtotime($point['date']) - $first) / $span, 1);
            $y = round(self::TOP + $plotHeight * (1 - max(0, min(100, $point['score'])) / 100), 1);
            $points[] = $x . ',' . $y;
            $dots .= '<circle cx="' . $x . '" cy="' . $y . '" r="3" fill="#2563eb"><title>'
                . htmlspecialchars(\Tools::displayDate($point['date']) . ' : ' . $point['score'] . '/100', ENT_QUOTES, 'UTF-8')
                . '</title></circle>';
        }

        $svg .= '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="#2563eb" stroke-width="2" stroke-linejoin="round"/>'
            . $dots
            . '<text x="' . self::LEFT . '" y="' . (self::HEIGHT - 6) . '">' . htmlspecialchars(\Tools::displayDate($series[0]['date']), ENT_QUOTES, 'UTF-8') . '</text>'
            . '<text x="' . (self::WIDTH - self::RIGHT) . '" y="' . (self::HEIGHT - 6) . '" text-anchor="end">'
            . htmlspecialchars(\Tools::displayDate($series[count($series) - 1]['date']), ENT_QUOTES, 'UTF-8') . '</text>'
            . '</svg>';

        return $svg;
    }

    /**
     * Change of the score since the previous snapshot.
     *
     * @param array<int, array{date: string, score: float}> $series Oldest first
     * @return array{value: float, label: string, since: string}|null
     */
    public static function delta(array $series)
    {
        $count = count($series);
        if ($count < 2) {
            return null;
        }

        $value = round($series[$count - 1]['score'] - $series[$count - 2]['score'], 1);

        return [
            'value' => $value,
            'label' => ($value > 0 ? '+' : '') . $value,
            'since' => \Tools::displayDate($series[$count - 2]['date']),
        ];
    }
}
