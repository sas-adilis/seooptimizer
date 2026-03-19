<?php

namespace Adilis\SeoOptimizer;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheManager
{
    const CACHE_DIRECTORY = _PS_ROOT_DIR_ . '/var/cache/seooptimizer/';

    private static $allowed_cache_file_extensions = ['txt', 'json'];

    /**
     * @throws \PrestaShopException
     */
    public static function delete(string $string)
    {
        $filesystem = new Filesystem();
        $filePath = self::getPath($string);
        if ($filesystem->exists($filePath)) {
            try {
                $filesystem->remove($filePath);
            } catch (IOException $e) {
                throw new \PrestaShopException($e->getMessage());
            }
        }
    }

    /**
     * @throws \PrestaShopException
     */
    public static function write(string $string, $content)
    {
        $filesystem = new Filesystem();
        if (!$filesystem->exists(self::CACHE_DIRECTORY)) {
            try {
                $filesystem->mkdir(self::CACHE_DIRECTORY);
            } catch (IOException $e) {
                throw new \PrestaShopException($e->getMessage());
            }
        }

        $filePath = self::getPath($string);
        if (!$filesystem->exists($filePath)) {
            try {
                $filesystem->touch($filePath);
            } catch (IOException $e) {
                throw new \PrestaShopException($e->getMessage());
            }
        }

        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT);
        }

        $filesystem->dumpFile($filePath, $content);

        return true;
    }

    public static function get(string $string)
    {
        $filePath = self::getPath($string);

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                return json_decode($content, true);
            }
        }

        return null;
    }

    private static function getPath(string $string): string
    {
        // Test if a extension is already present
        if (
            ($extension = pathinfo($string, PATHINFO_EXTENSION)) === ''
            || !in_array($extension, self::$allowed_cache_file_extensions)
        ) {
            $string .= '.json';
        }

        return self::CACHE_DIRECTORY . $string;
    }

    /**
     * @throws \PrestaShopException
     */
    public static function getDateFor(string $string)
    {
        $filePath = self::getPath($string);
        if (file_exists($filePath)) {
            $date = date('Y-m-d H:i:s', filectime($filePath));

            return \Tools::displayDate($date, true);
        }

        return 0;
    }

    public static function exists(string $string)
    {
        return file_exists(self::getPath($string));
    }
}
