<?php

namespace Adilis\SeoOptimizer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class UrlHelper
{
    public static function is404($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);  // Limite de redirections pour éviter les boucles infinies
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Suivre les redirections si nécessaire
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);  // Timeout en secondes
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === null || $code == 404) {
            return true;
        }

        return false;
    }

    public static function isAbsolute($url)
    {
        return (bool) preg_match('/^https?:\/\//', $url);
    }
}
