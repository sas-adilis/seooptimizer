<?php

namespace Adilis\SeoOptimizer\Content\Report;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\EntityDefinition\EntityDefinitionInterface;
use Adilis\SeoOptimizer\TranslateHelper;

class ReportNotFoundLinks extends Report implements ReportInterface
{
    const LOG_REPORT = true;

    public function getContent(): string
    {
        return $this->renderFormReport($this->l('Links 404'), 'icon-unlink');
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
        $all_links = $this->extract404Links($all_links);

        foreach ($founded_elements as $key => $founded_element) {
            if (!in_array($founded_element['url'], $all_links)) {
                unset($founded_elements[$key]);
            }
        }

        return [$founded_elements, count($founded_elements), 0];
    }

    public function extractLinks($content): array
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

        $images = $xpath->evaluate('/html/body//img');
        for ($i = 0; $i < $images->length; ++$i) {
            $img = $images->item($i);
            $src = $img->getAttribute('src');
            $links[] = $src;
        }

        return $links;
    }

    public function extract404Links($urls, $maxSimultaneousConnections = 100): array
    {

        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $results404 = [];

        $urlChunks = array_chunk($urls, $maxSimultaneousConnections);

        foreach ($urlChunks as $chunk) {
            if (self::LOG_REPORT) {
                $handle = fopen(sprintf(
                    '%s/var/logs/%s.txt',
                    _PS_ROOT_DIR_,
                    $this->getKey()
                ), 'a+');
                $start_time = microtime(true);
                fwrite($handle, implode(PHP_EOL, $chunk));
            }

            foreach ($chunk as $url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_NOBODY, true); // Ne pas récupérer le contenu
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Ne pas afficher le résultat directement
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
                curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout de 10 secondes

                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[$url] = $ch;
            }

            $running = 0;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            foreach ($curlHandles as $url => $ch) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpCode == 404) {
                    $results404[] = $url;
                }

                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
            $curlHandles = [];

            if (self::LOG_REPORT && isset($handle) && isset($start_time)) {
                $duration = microtime(true) - $start_time;
                fwrite($handle, 'Duration : ' . $duration . PHP_EOL. PHP_EOL);
                fclose($handle);
            }
        }

        curl_multi_close($multiHandle);

        return $results404;
    }

    public function getReportFields(): array
    {
        /* todo: Use translation system */
        return [
            'page' => 'Page',
            'url' => 'URL',
        ];
    }

    public function getAllowedFieldsTypes(): array
    {
        return [
            Constants::HTML_FIELD,
        ];
    }

    public function getDescription(): string
    {
        return TranslateHelper::get()->l('This tool scans the HTML content of your various Prestashop entities to detect links or images pointing to 404 (not found) pages.');
    }

    public function canFix(): bool
    {
        return \Configuration::get('SEOO_FIX_NOT_FOUND_LINKS_METHOD') !== Constants::FIX_METHOD_IGNORE;
    }
}
