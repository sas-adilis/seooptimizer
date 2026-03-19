<?php

namespace Adilis\SeoOptimizer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RichSnippetSearcher
{
    private $tplFiles;

    private static $patternMatches = [
        '/itemprop="[^"]*"/',
        '/itemscope(="[^"]*")?/',
        '/itemtype="[^"]*"/',
        '/typeof="[^"]*"/',
        "/<script[^>]*type=['\"]application\/ld\+json['\"][^>]*>/",
    ];

    public function __construct()
    {
        $smartyFileScanner = new SmartyFileIndexer();
        $this->tplFiles = $smartyFileScanner->getTplFiles();
    }

    public function search(): array
    {
        $start_time = microtime(true);
        $results = [];
        foreach ($this->tplFiles as $file) {
            $lines = file($file); // Read the file into an array, each line is an element
            foreach ($lines as $lineNumber => $lineContent) {
                foreach (self::$patternMatches as $pattern) {
                    if (preg_match($pattern, $lineContent, $matches)) {
                        // Store the file name, line number, and matched text
                        $results[] = [
                            'file' => Utils::removePsRootFromPath($file),
                            'line' => $lineNumber + 1, // Line numbers are 0-indexed, so add 1
                            'text' => trim($matches[0]), // Use the full matched text
                        ];
                        break; // Stop checking other patterns for this line if a match is found
                    }
                }
            }
        }
        //dump($results);
        $duration = microtime(true) - $start_time;
        //dump('Rich snippet search duration: ' . $duration);
        return $results;

    }
}