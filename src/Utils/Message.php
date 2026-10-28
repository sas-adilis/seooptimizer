<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Translatable messages of the audits.
 *
 * The messages of an audit are stored in the database: they are stored in English, with their
 * template and arguments, and translated when displayed (in the language of the employee).
 *
 * - t()         builds the English message and remembers its template, so the storage can keep it;
 * - translate() translates a message built by t() during the current request, or a literal;
 * - tr()        translates immediately a template (labels that are not stored);
 * - render()    translates a stored template with its arguments.
 *
 * An argument that is an array is a list of strings to translate and join with ", ".
 */
class Message
{
    /** @var array<string, array{0: string, 1: array}> English message => [template, arguments] */
    private static $registry = [];

    /**
     * @param string $template English template (sprintf format)
     * @param mixed ...$args
     */
    public static function t(string $template, ...$args): string
    {
        $message = self::format($template, $args, false);
        self::$registry[$message] = [$template, $args];

        return $message;
    }

    /**
     * Translated message, immediately.
     *
     * @param string $template
     * @param mixed ...$args
     */
    public static function tr(string $template, ...$args): string
    {
        return self::render($template, $args);
    }

    /**
     * Translation of a message built by t() in this request (or of a literal string).
     */
    public static function translate(string $message): string
    {
        if (isset(self::$registry[$message])) {
            return self::render(self::$registry[$message][0], self::$registry[$message][1]);
        }

        return self::translateString($message);
    }

    /**
     * Template and arguments of a message built by t() in this request.
     *
     * @return array{0: string, 1: array}|null
     */
    public static function lookup(string $message)
    {
        return isset(self::$registry[$message]) ? self::$registry[$message] : null;
    }

    /**
     * Strings of the JavaScript files, translated (English string => translation).
     *
     * @return array<string, string>
     */
    public static function getJsStrings(): array
    {
        return [
            'Crawling...' => self::tr('Crawling...'),
            'Resume' => self::tr('Resume'),
            'Audit interrupted' => self::tr('Audit interrupted'),
            'Done' => self::tr('Done'),
            'In progress' => self::tr('In progress'),
            'Waiting' => self::tr('Waiting'),
            'Start full audit' => self::tr('Start full audit'),
            'Error' => self::tr('Error'),
            'The analysis was interrupted. Click the button again to resume it.' => self::tr('The analysis was interrupted. Click the button again to resume it.'),
            'Unable to start the analysis.' => self::tr('Unable to start the analysis.'),
            'Audit failed' => self::tr('Audit failed'),
            'Parse error' => self::tr('Parse error'),
            'SEO Audit' => self::tr('SEO Audit'),
            'Score' => self::tr('Score'),
            'Are you sure you want to stop the process?' => self::tr('Are you sure you want to stop the process?'),
            'A process is already running.' => self::tr('A process is already running.'),
        ];
    }

    /**
     * @param string $template
     * @param array $args
     */
    public static function render(string $template, array $args = []): string
    {
        return self::format(self::translateString($template), $args, true);
    }

    public static function translateString(string $string): string
    {
        if ($string === '') {
            return $string;
        }

        return \Translate::getModuleTranslation('seooptimizer', $string, 'seooptimizer', null, false, null, true, false);
    }

    /**
     * @param string $template
     * @param array $args
     * @param bool $translateLists Translate the strings of the array arguments
     */
    private static function format(string $template, array $args, bool $translateLists): string
    {
        if (empty($args)) {
            return $template;
        }

        foreach ($args as $i => $arg) {
            if (is_array($arg)) {
                $args[$i] = implode(', ', $translateLists ? array_map([self::class, 'translateString'], $arg) : $arg);
            }
        }

        return vsprintf($template, $args);
    }
}
