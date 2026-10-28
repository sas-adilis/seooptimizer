<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Minimal robots.txt reader following the Google rules: the most specific group
 * (Googlebot, otherwise "*"), "*" and "$" wildcards, longest rule wins, Allow wins a tie.
 */
class RobotsTxt
{
    /**
     * @param string $content Content of robots.txt
     * @param string $pathWithQuery Path of the URL, followed by its query string if any
     *
     * @return bool
     */
    public static function isBlocked(string $content, string $pathWithQuery): bool
    {
        $rules = self::getRules($content);
        $bestLength = -1;
        $blocked = false;

        foreach ($rules as $rule) {
            if ($rule['pattern'] === '' || !self::matches($rule['pattern'], $pathWithQuery)) {
                continue;
            }

            $length = strlen($rule['pattern']);
            if ($length > $bestLength || ($length === $bestLength && $rule['allow'])) {
                $bestLength = $length;
                $blocked = !$rule['allow'];
            }
        }

        return $blocked;
    }

    /**
     * Rules of the group that applies to Googlebot.
     *
     * @param string $content
     *
     * @return array<int, array{allow: bool, pattern: string}>
     */
    private static function getRules(string $content): array
    {
        $groups = [];
        $agents = [];
        $lastWasAgent = false;

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }

            list($field, $value) = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if (!$lastWasAgent) {
                    $agents = [];
                }
                $agents[] = strtolower($value);
                $lastWasAgent = true;
                continue;
            }

            $lastWasAgent = false;
            if ($field !== 'allow' && $field !== 'disallow') {
                continue;
            }
            foreach ($agents as $agent) {
                $groups[$agent][] = ['allow' => $field === 'allow', 'pattern' => $value];
            }
        }

        if (isset($groups['googlebot'])) {
            return $groups['googlebot'];
        }

        return isset($groups['*']) ? $groups['*'] : [];
    }

    private static function matches(string $pattern, string $path): bool
    {
        $regex = '';
        foreach (preg_split('/([*$])/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if ($part === '*') {
                $regex .= '.*';
            } elseif ($part === '$') {
                $regex .= '$';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        return (bool) preg_match('#^' . $regex . '#', $path);
    }
}
