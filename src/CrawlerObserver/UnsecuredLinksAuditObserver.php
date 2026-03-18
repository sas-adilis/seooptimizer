<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

class UnsecuredLinksAuditObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var int */
    private $linksChecked = 0;

    public function getKey(): string
    {
        return 'unsecured_links_audit';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $bodyContent = $content;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }

        if (empty(trim($bodyContent))) {
            return;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $bodyContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $this->checkElements($dom, 'a', 'href', $url);
        $this->checkElements($dom, 'img', 'src', $url);
        $this->checkElements($dom, 'script', 'src', $url);
        $this->checkLinkElements($dom, $url);
        $this->checkElements($dom, 'source', 'src', $url);
        $this->checkElements($dom, 'video', 'src', $url);
        $this->checkElements($dom, 'audio', 'src', $url);
        $this->checkElements($dom, 'iframe', 'src', $url);
    }

    /**
     * @param \DOMDocument $dom
     * @param string $tagName
     * @param string $attribute
     * @param string $pageUrl
     */
    private function checkElements(\DOMDocument $dom, string $tagName, string $attribute, string $pageUrl)
    {
        $elements = $dom->getElementsByTagName($tagName);
        foreach ($elements as $element) {
            $value = $element->getAttribute($attribute);
            if (!$value) {
                continue;
            }

            $this->linksChecked++;

            if ($this->isHttpUrl($value)) {
                $text = '';
                if ($tagName === 'a') {
                    $text = mb_substr(trim(strip_tags($element->nodeValue)), 0, 80);
                } elseif ($tagName === 'img') {
                    $text = $element->getAttribute('alt') ?: '[image]';
                } else {
                    $text = '[' . $tagName . ']';
                }

                $this->results[] = [
                    'page_url' => $pageUrl,
                    'unsecured_url' => $value,
                    'element' => '<' . $tagName . ' ' . $attribute . '>',
                    'link_text' => $text,
                ];
            }
        }
    }

    /**
     * @param \DOMDocument $dom
     * @param string $pageUrl
     */
    private function checkLinkElements(\DOMDocument $dom, string $pageUrl)
    {
        $elements = $dom->getElementsByTagName('link');
        foreach ($elements as $element) {
            $href = $element->getAttribute('href');
            if (!$href) {
                continue;
            }

            $this->linksChecked++;

            if ($this->isHttpUrl($href)) {
                $rel = $element->getAttribute('rel') ?: 'link';
                $this->results[] = [
                    'page_url' => $pageUrl,
                    'unsecured_url' => $href,
                    'element' => '<link rel="' . $rel . '">',
                    'link_text' => '[' . $rel . ']',
                ];
            }
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isHttpUrl(string $url): bool
    {
        return strpos($url, 'http://') === 0;
    }

    /**
     * @return int
     */
    public function getLinksChecked(): int
    {
        return $this->linksChecked;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
