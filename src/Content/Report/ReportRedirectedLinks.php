<?php

namespace Adilis\SeoOptimizer\Content\Report;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\EntityDefinition\EntityDefinitionInterface;

class ReportRedirectedLinks extends Report implements ReportInterface
{
    public function getContent(): string
    {
        return $this->renderFormReport($this->l('Redirected links'), 'icon-share');
    }

    /**
     * @param EntityDefinitionInterface $definition
     * @param array $rows
     * @param bool $shouldFix
     *
     * @return array{0: array, 1: int, 2: int}
     */
    public function run(EntityDefinitionInterface $definition, array $rows = [], bool $shouldFix = false): array
    {
        $founded_elements = [];
        foreach ($rows as $row) {
            foreach ($definition->getFields() as $field => $field_type) {
                if ($field_type !== Constants::HTML_FIELD) {
                    continue;
                }
                $html_content = $row[$field];
                $extracted_links = $this->extractLinks($html_content);
                foreach ($extracted_links as $link) {
                    $id_primary = $row[$definition->getPrimaryKey()];
                    $founded_elements[] = [
                        'id_primary' => $id_primary,
                        'page' => $definition->getLink($id_primary, $row['id_lang'], $row['id_shop'] ?? null),
                        'url' => $link,
                    ];
                }
            }
        }

        $all_links = array_column($founded_elements, 'url');
        $all_links = array_unique($all_links);

        $all_links = $this->extractRedirectedLinks($all_links);
        foreach ($founded_elements as $key => $founded_element) {
            if (!isset($all_links[$founded_element['url']])) {
                unset($founded_elements[$key]);
            } else {
                $founded_elements[$key]['url_replacement'] = $all_links[$founded_element['url']];
            }
        }

        return [$founded_elements, count($founded_elements), 0];
    }

    public function extractLinks($content)
    {
        $links = [];
        if (empty($content)) {
            return $links;
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML($content);
        $xpath = new \DOMXPath($dom);
        $hrefs = $xpath->evaluate('/html/body//a');
        for ($i = 0; $i < $hrefs->length; ++$i) {
            $href = $hrefs->item($i);
            $url = $href->getAttribute('href');
            $links[] = $url;
        }

        return $links;
    }

    public function extractRedirectedLinks($urls, $maxSimultaneousConnections = 100)
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $resultsRedirects = [];

        $urlChunks = array_chunk($urls, $maxSimultaneousConnections);

        foreach ($urlChunks as $chunk) {
            foreach ($chunk as $url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[$url] = $ch;
            }

            $running = 0;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            foreach ($curlHandles as $originalUrl => $ch) {
                $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // Exclure si l'URL finale est identique à l'URL d'origine ou si elle retourne un code HTTP 404
                if ($finalUrl !== $originalUrl && $httpCode != 404) {
                    $resultsRedirects[$originalUrl] = $httpCode != 404 ? $finalUrl : '';
                }

                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }

            $curlHandles = [];
        }

        curl_multi_close($multiHandle);

        return $resultsRedirects;
    }

    public function getReportFields(): array
    {
        return [
            'page' => 'Page',
            'url' => 'URL',
            'url_replacement' => 'URL replacement',
        ];
    }

    public function getAllowedFieldsTypes(): array
    {
        return [
            Constants::HTML_FIELD,
        ];
    }
}
