<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Central registry of all available audits.
 * Single source of truth — eliminates duplicated audit lists across the module.
 */
class AuditRegistry
{
    /** @var AuditInterface[]|null */
    private static $audits = null;

    /**
     * @return AuditInterface[]
     */
    public static function getAll(): array
    {
        if (self::$audits === null) {
            self::$audits = [
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
                new AuditStructuredData(),
            ];
        }

        return self::$audits;
    }

    /**
     * @param string $key
     * @return AuditInterface|null
     */
    public static function get(string $key)
    {
        foreach (self::getAll() as $audit) {
            if ($audit->getKey() === $key) {
                return $audit;
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    public static function getKeys(): array
    {
        return array_map(function (AuditInterface $a) {
            return $a->getKey();
        }, self::getAll());
    }
}
