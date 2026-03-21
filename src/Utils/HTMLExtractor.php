<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

class HTMLExtractor
{
    /** @var \DOMDocument */
    private $dom;

    /** @var \DOMXPath */
    private $xpath;

    /** @var string */
    private $html;

    /**
     * @param string $html
     */
    public function __construct(string $html)
    {
        $this->html = $html;
        $this->dom = new \DOMDocument();
        @$this->dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $this->xpath = new \DOMXPath($this->dom);
    }

    /**
     * @return \DOMDocument
     */
    public function getDOM(): \DOMDocument
    {
        return $this->dom;
    }

    /**
     * @return \DOMXPath
     */
    public function getXPath(): \DOMXPath
    {
        return $this->xpath;
    }

    /**
     * @return string
     */
    public function getRawHTML(): string
    {
        return $this->html;
    }

    /**
     * Extract the <title> tag content.
     *
     * @return string
     */
    public function extractTitle(): string
    {
        $nodes = $this->dom->getElementsByTagName('title');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return '';
    }

    /**
     * Extract meta description content.
     *
     * @return string
     */
    public function extractMetaDescription(): string
    {
        $metas = $this->dom->getElementsByTagName('meta');
        foreach ($metas as $m) {
            if (strtolower($m->getAttribute('name')) === 'description') {
                return trim($m->getAttribute('content'));
            }
        }
        return '';
    }

    /**
     * Extract meta keywords content.
     *
     * @return string
     */
    public function extractMetaKeywords(): string
    {
        $metas = $this->dom->getElementsByTagName('meta');
        foreach ($metas as $m) {
            if (strtolower($m->getAttribute('name')) === 'keywords') {
                return trim($m->getAttribute('content'));
            }
        }
        return '';
    }

    /**
     * Extract meta robots content.
     *
     * @return string
     */
    public function extractMetaRobots(): string
    {
        $metas = $this->dom->getElementsByTagName('meta');
        foreach ($metas as $m) {
            if (strtolower($m->getAttribute('name')) === 'robots') {
                return strtolower(trim($m->getAttribute('content')));
            }
        }
        return '';
    }

    /**
     * Extract canonical URL.
     *
     * @return string
     */
    public function extractCanonical(): string
    {
        $links = $this->xpath->query('//link[@rel="canonical"]');
        if ($links->length > 0) {
            return $links->item(0)->getAttribute('href');
        }
        return '';
    }

    /**
     * Extract hreflang alternate links.
     *
     * @return array<int, array{lang: string, href: string}>
     */
    public function extractHreflangs(): array
    {
        $hreflangs = [];
        $links = $this->xpath->query('//link[@rel="alternate"][@hreflang]');
        foreach ($links as $link) {
            $lang = $link->getAttribute('hreflang');
            $href = $link->getAttribute('href');
            if ($lang && $href) {
                $hreflangs[] = ['lang' => $lang, 'href' => $href];
            }
        }
        return $hreflangs;
    }

    /**
     * Extract all headings (H1-H6).
     *
     * @return array<int, array{level: int, text: string}>
     */
    public function extractHeadings(): array
    {
        $headings = [];
        $nodes = $this->xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        foreach ($nodes as $el) {
            $level = (int) substr($el->tagName, 1);
            $text = trim(preg_replace('/\s+/', ' ', $el->textContent));
            $headings[] = [
                'level' => $level,
                'text' => $text,
            ];
        }
        return $headings;
    }

    /**
     * Extract body text content (stripped of scripts, styles, nav, header, footer).
     *
     * @return string
     */
    public function extractBodyText(): string
    {
        $bodyContent = $this->html;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $this->html, $m)) {
            $bodyContent = $m[1];
        }

        $bodyContent = preg_replace('/<(script|style|nav|header|footer)[^>]*>.*?<\/\1>/is', '', $bodyContent);
        return strip_tags($bodyContent);
    }

    /**
     * Extract body HTML (raw, between <body> tags).
     *
     * @return string
     */
    public function extractBodyHTML(): string
    {
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $this->html, $m)) {
            return $m[1];
        }
        return $this->html;
    }

    /**
     * Extract all images with their attributes.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    public function extractImages(): array
    {
        $images = [];
        $nodes = $this->dom->getElementsByTagName('img');
        foreach ($nodes as $img) {
            $src = $img->getAttribute('src');
            if (empty($src) || strpos($src, 'data:') === 0) {
                continue;
            }
            $images[] = [
                'src' => $src,
                'alt' => trim($img->getAttribute('alt')),
                'width' => $img->getAttribute('width'),
                'height' => $img->getAttribute('height'),
            ];
        }
        return $images;
    }

    /**
     * Extract all anchor links.
     *
     * @return array<int, array{href: string, text: string, rel: string}>
     */
    public function extractAnchors(): array
    {
        $links = [];
        $anchors = $this->dom->getElementsByTagName('a');
        foreach ($anchors as $a) {
            $href = $a->getAttribute('href');
            if (empty($href)) {
                continue;
            }
            $links[] = [
                'href' => $href,
                'text' => trim(strip_tags($a->nodeValue)),
                'rel' => strtolower($a->getAttribute('rel')),
            ];
        }
        return $links;
    }

    /**
     * Extract all resource links (scripts, stylesheets, images).
     *
     * @return array<int, array{url: string, type: string}>
     */
    public function extractResources(): array
    {
        $resources = [];

        $scripts = $this->dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            if ($src) {
                $resources[] = ['url' => $src, 'type' => 'script'];
            }
        }

        $links = $this->dom->getElementsByTagName('link');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $rel = $link->getAttribute('rel');
            if ($href && $rel === 'stylesheet') {
                $resources[] = ['url' => $href, 'type' => 'stylesheet'];
            }
        }

        $images = $this->dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && strpos($src, 'data:') !== 0) {
                $resources[] = ['url' => $src, 'type' => 'image'];
            }
        }

        return $resources;
    }

    /**
     * Extract all JSON-LD structured data blocks.
     *
     * @return array<int, array> Decoded JSON-LD items
     */
    public function extractJsonLd(): array
    {
        $items = [];
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($scripts as $script) {
            $json = json_decode(trim($script->textContent), true);
            if (!$json) {
                continue;
            }
            if (isset($json['@graph'])) {
                foreach ($json['@graph'] as $item) {
                    $items[] = $item;
                }
            } else {
                $items[] = $json;
            }
        }
        return $items;
    }

    /**
     * Extract all schema.org @type values from JSON-LD.
     *
     * @return array<string, bool> type => true
     */
    public function extractSchemaTypes(): array
    {
        $types = [];
        foreach ($this->extractJsonLd() as $item) {
            if (isset($item['@type'])) {
                $itemTypes = is_array($item['@type']) ? $item['@type'] : [$item['@type']];
                foreach ($itemTypes as $type) {
                    $types[$type] = true;
                }
            }
        }
        return $types;
    }

    /**
     * Concatenate all image alt texts.
     *
     * @return string
     */
    public function extractAllAltTexts(): string
    {
        $alts = '';
        foreach ($this->extractImages() as $img) {
            if (!empty($img['alt'])) {
                $alts .= ' ' . $img['alt'];
            }
        }
        return trim($alts);
    }

    /**
     * Check if the page is a product page (heuristic).
     *
     * @return bool
     */
    public function isProductPage(): bool
    {
        return (bool) preg_match('/product-page|id_product|product\.tpl|"@type"\s*:\s*"Product"/i', $this->html);
    }

    /**
     * Check if the page has noindex directive.
     *
     * @return bool
     */
    public function isNoindex(): bool
    {
        $robots = $this->extractMetaRobots();
        return strpos($robots, 'noindex') !== false || strpos($robots, 'none') !== false;
    }
}
