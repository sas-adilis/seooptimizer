<?php

namespace Adilis\SeoOptimizer;

use Adilis\SeoOptimizer\CrawlerObserver\CrawlerObserverInterface;
use Adilis\SeoOptimizer\CrawlerObserver\DuplicateH1;
use Adilis\SeoOptimizer\CrawlerObserver\MissingAltAttributeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\ResponseTimeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\UnsecuredLinksObserver;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ShopCrawler
{
    private $client;
    private $urls = [];
    private $contents = [];
    private $results = [];
    private $observers = [];

    public function __construct(array $urls = [])
    {
        $this->urls = $urls;
        $this->client = new Client([
            'timeout' => 10.0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; ShopCrawler/1.0)',
            ],
        ]);

        $this->addObserver(new DuplicateH1());
        $this->addObserver(new ResponseTimeObserver());
        $this->addObserver(new UnsecuredLinksObserver());
        $this->addObserver(new MissingAltAttributeObserver());
    }

    public function addUrl($urls)
    {
        if (is_array($urls)) {
            $this->urls = array_merge($this->urls, $urls);
        } else {
            $this->urls[] = $urls;
        }
    }

    public function addObserver(CrawlerObserverInterface $observer)
    {
        $this->observers[$observer->getKey()] = $observer;
    }

    public function crawl()
    {
        foreach ($this->urls as $url) {
            $this->notifyObserversBeforeRequest($url);

            try {
                $response = $this->client->get($url);
                $content = $response->getBody()->getContents();
                $this->contents[$url] = $content;
                $this->notifyObserversAfterRequest($url, $content);
            } catch (RequestException $e) {
                $this->contents[$url] = 'Erreur : ' . $e->getMessage();
            }
        }

        $this->storeObserverResults();
    }

    private function notifyObserversBeforeRequest(string $url)
    {
        foreach ($this->observers as $observer) {
            if (method_exists($observer, 'observeBeforeRequest')) {
                $observer->observeBeforeRequest($url);
            }
        }
    }

    private function notifyObserversAfterRequest(string $url, string $content)
    {
        foreach ($this->observers as $observer) {
            if (method_exists($observer, 'observeAfterRequest')) {
                $observer->observeAfterRequest($url, $content);
            }
        }
    }

    private function storeObserverResults()
    {
        foreach ($this->observers as $observer) {
            $this->results[$observer->getKey()] = $observer->getResults();
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
