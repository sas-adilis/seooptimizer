<?php

namespace Adilis\SeoOptimizer\HtmlOutputBefore;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LinkObfuscator
{
    /**
     * @throws \DOMException
     */
    public function process(string &$html)
    {
        if (!\Configuration::get('SEOO_ENABLE_LINK_OBFUSCATION') || empty($html)) {
            return;
        }

        $start_time = microtime(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $anchor) {
            if ($anchor->hasAttribute('data-obfuscate')) {
                $originalUrl = $anchor->getAttribute('href');
                $linkText = $anchor->nodeValue;
                $encodedUrl = base64_encode($originalUrl);

                $newSpan = $dom->createElement('span', $linkText);
                $newSpan->setAttribute('data-obfuscate', '');
                $newSpan->setAttribute('onclick', "window.location.href=atob('{$encodedUrl}');");

                foreach ($anchor->attributes as $attr) {
                    if ($attr->nodeName !== 'href' && $attr->nodeName !== 'data-obfuscate') {
                        $newSpan->setAttribute($attr->nodeName, $attr->nodeValue);
                    }
                }

                $anchor->parentNode->replaceChild($newSpan, $anchor);
            }
        }

        $html = $dom->saveHTML();
    }

}