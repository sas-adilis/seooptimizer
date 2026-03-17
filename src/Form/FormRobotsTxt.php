<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\Utils;

class FormRobotsTxt extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        $robots_txt_content = \Tools::getValue(
            'SEOO_ROBOTS_TXT',
            \Tools::file_get_contents(_PS_ROOT_DIR_ . '/robots.txt')
        );

        if ($robots_txt_content === false) {
            $robots_txt_content = '';
        }

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Robots.txt'),
                    'icon' => 'icon-file-text',
                    'visual' => __PS_BASE_URI__ . 'modules/seooptimizer/views/img/panda-robots.png',
                    'description' => $this->l('Edit the content of your robots.txt file. This file tells search engines which pages they can or cannot crawl on your site.'),
                ],
                'input' => [
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Content'),
                        'name' => 'SEOO_ROBOTS_TXT',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
                'buttons' => [
                    [
                        'title' => $this->l('Reset robots.txt'),
                        'type' => 'submit',
                        'class' => 'btn btn-default',
                        'name' => 'submit' . $this->getKey() . 'Reset',
                    ],
                ],
            ],
        ], ['SEOO_ROBOTS_TXT' => $robots_txt_content]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function postProcess()
    {
        $content = \Tools::getValue('SEOO_ROBOTS_TXT');
        $content = str_replace("\n", PHP_EOL, $content);
        $context = \Context::getContext();

        $content_before = $this->getRobotsTxtContent();

        if ($content === $content_before) {
            $context->controller->errors[] = $this->l('The content of the robots.txt file has not been modified');

            return;
        }

        // Todo: truncate history

        // First, save in cache
        if (!CacheManager::write(
            sprintf('robots_%s.txt', date('Y-m-d-H-i-s')),
            $content_before
        )) {
            $context->controller->errors[] = $this->l('An error occurred while saving the robots.txt file');

            return;
        }

        if (file_put_contents(_PS_ROOT_DIR_ . '/robots.txt', $content)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            $context->controller->errors[] = $this->l('An error occurred while writing the robots.txt file');
        }
    }

    /**
     * @throws \PrestaShopException
     */
    public function postProcessReset()
    {
        $content_before = $this->getRobotsTxtContent();
        $context = \Context::getContext();

        // Todo: truncate history

        // First, save in cache
        if (!CacheManager::write(
            sprintf('robots_%s.txt', date('Y-m-d-H-i-s')),
            $content_before
        )) {
            $context->controller->errors[] = $this->l('An error occurred while saving the robots.txt file');

            return;
        }

        if (\Tools::generateRobotsFile(true)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            $context->controller->errors[] = $this->l('An error occurred while writing the robots.txt file');
        }
    }

    private function getRobotsTxtContent(): string
    {
        $content = \Tools::file_get_contents(_PS_ROOT_DIR_ . '/robots.txt');
        if ($content === false) {
            $content = '';
        }

        return $content;
    }
}
