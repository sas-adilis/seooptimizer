<?php

namespace Adilis\SeoOptimizer\Content\Report;

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\EntityDefinition\EntityDefinitionInterface;
use Adilis\SeoOptimizer\EntityDefinition\EntityDefinitionLoader;
use Adilis\SeoOptimizer\Utils;

abstract class Report implements ReportInterface
{
    private $start_process_time;
    /**
     * @var array|null
     */
    private $report;

    public function getKey($to_underscore_case = false): string
    {
        $class_name = (new \ReflectionClass($this))->getShortName();
        if ($to_underscore_case) {
            return \Tools::toUnderscoreCase($class_name);
        }

        return $class_name;
    }

    public function getReportFields(): array
    {
        return [];
    }

    /**
     * @throws \PrestaShopException
     */
    public function process()
    {
        if (\Tools::isSubmit('download' . $this->getKey())) {
            $this->processDownload();
        }

        if ((int) \Tools::getValue('ajax')) {
            $action = \Tools::getValue('action');
            $first_process = \Tools::getValue('first_process') === 'true';
            if ($action === 'runAndFix' . $this->getKey()) {
                $this->ajaxProcessRun(true, $first_process);
            }

            if ($action === 'run' . $this->getKey()) {
                $this->ajaxProcessRun(false, $first_process);
            }

            if ($action === 'continue' . $this->getKey()) {
                $report = CacheManager::get($this->getKey(true));
                if (!$report || !isset($report['status']) || $report['status'] !== Constants::REPORT_PARTIAL) {
                    $this->returnJsonError('No report to continue');
                }
                $this->ajaxProcessRun($report['should_fix']);
            }
        }

        \Context::getContext()->smarty->assign($this->getKey(true), $this->getContent());
    }

    public function canFix(): bool
    {
        return true;
    }

    public function getContent(): string
    {
        return '';
    }

    public function run(EntityDefinitionInterface $definition, array $rows = [], bool $shouldFix = false): array
    {
        return [[], 0, 0];
    }

    protected function renderFormReport(string $title, string $icon = 'icon-cogs'): string
    {
        $definitions = self::getDefinitionInstances(true);
        $report = CacheManager::get($this->getKey(true));
        if ($report) {
            foreach ($definitions as $definition) {
                if (
                    isset($report['items'][$definition->getKey()])
                    && ($current = $report['items'][$definition->getKey()])
                ) {
                    $definition->setProgress($current['progress']);
                    $definition->setResultsCount((int) $current['results_count']);
                    $definition->setProgressPercentage((float) ($current['percentage'] ?? 0));
                    if ($this->canFix()) {
                        $definition->setFixedCount((int) $current['fixed_count']);
                    }
                }
            }
        }

        $buttons = [];

        if ($this->canFix()) {
            $buttons[] = [
                'title' => $this->l('Analyze and fix'),
                'type' => 'button',
                'name' => 'runAndFix' . $this->getKey(),
                'class' => 'process-icon-wrench runAjaxProcess pull-right',
            ];
        }

        $buttons[] = [
            'title' => $this->l('Analyze'),
            'type' => 'button',
            'name' => 'run' . $this->getKey(),
            'class' => 'process-icon-search runAjaxProcess pull-right',
        ];

        $buttons[] = [
            'title' => $this->l('Abort'),
            'type' => 'button',
            'name' => 'abort' . $this->getKey(),
            'class' => 'btn-danger process-icon-stop pull-right',
        ];

        if ($report && isset($report['status'])) {
            switch ($report['status']) {
                case Constants::REPORT_PARTIAL:
                    $buttons[] = [
                        'title' => $this->l('Continue partial report'),
                        'type' => 'submit',
                        'name' => 'continue' . $this->getKey(),
                        'class' => 'process-icon-refresh runAjaxProcess',
                    ];
                    break;
                case Constants::REPORT_COMPLETED:
                    $buttons[] = [
                        'title' => sprintf(
                            $this->l('Download last report (%s)'),
                            \Tools::displayDate($report['date'], true)
                        ),
                        'class' => 'process-icon-download',
                        'type' => 'submit',
                        'name' => 'download' . $this->getKey(),
                    ];
                    break;
            }
        }

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $title,
                    'icon' => $icon,
                ],
                'desc' => $this->getDescription(),
                'input' => [
                    [
                        'type' => 'report',
                        'name' => $this->getKey(),
                        'definitions' => $definitions,
                        'show_fixed' => $this->canFix(),
                    ],
                ],
                'buttons' => $buttons,
            ],
        ], []);
    }

    public function renderForm(array $form, array $fields_value = []): string
    {
        $context = \Context::getContext();

        $helper = new \HelperForm();
        $helper->id = $this->getKey();
        $helper->show_toolbar = false;
        $helper->table = $this->getKey();
        $helper->module = \Module::getInstanceByName(Utils::MODULE_NAME);
        $helper->default_form_language = $context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $helper->id;
        $helper->submit_action = 'submitFormModule';
        $helper->currentIndex = $context->link->getAdminLink(
            'AdminModules',
            false,
            [],
            ['configure' => Utils::MODULE_NAME, 'module_name' => Utils::MODULE_NAME]
        );
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'languages' => $context->controller->getLanguages(),
            'id_language' => $context->language->id,
            'fields_value' => $fields_value,
        ];

        return $helper->generateForm([$form]);
    }

    protected function l($string)
    {
        // todo: implement translation
        return $string;
    }

    public function processDownload()
    {
        if (!CacheManager::exists($this->getKey(true))) {
            throw new \PrestaShopException('No report to download');
        }

        $report = CacheManager::get($this->getKey(true));
        if (!isset($report['items'])) {
            throw new \PrestaShopException('No report to download');
        }

        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }

        $filename = sprintf(
            '%s-%s.csv',
            $this->getKey(true),
            str_replace([' ', ':'], '_', $report['date'])
        );

        header('Cache-Control: no-cahe, must-revalidate');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $df = fopen('php://output', 'w+');
        fputs($df, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($df, array_values($this->getReportFields()), ';');

        foreach ($report['items'] as $data) {
            foreach ($data['results'] as $resultArray) {
                $current_row = [];
                foreach (array_keys($this->getReportFields()) as $key) {
                    $current_row[] = $resultArray[$key] ?? '';
                }
                fputcsv($df, $current_row, ';');
            }
        }

        fclose($df);

        exit;
    }

    private function getDefinitionInstances($clear_cache = false)
    {
        $definitions = EntityDefinitionLoader::getInstances($clear_cache);

        return array_filter($definitions, function ($definition) {
            return count(array_intersect($definition->getFields(), $this->getAllowedFieldsTypes())) > 0;
        });
    }

    public function ajaxProcessRun(bool $shouldFix = false, $first_process = false)
    {
        $this->start_process_time = microtime(true);

        if ($first_process) {
            try {
                CacheManager::delete($this->getKey(true));
            } catch (\PrestaShopException $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
                exit;
            }
        }

        $definitions = self::getDefinitionInstances();

        $this->report = CacheManager::get($this->getKey(true));

        if (!isset($this->report['status'])) {
            $this->report = [
                'status' => Constants::REPORT_PARTIAL,
                'duration' => 0,
                'items' => [],
                'should_fix' => $shouldFix,
            ];
        }

        foreach ($definitions as &$definition) {
            if (!isset($this->report['items'][$definition->getKey()])) {
                $this->report['items'][$definition->getKey()] = [
                    'status' => Constants::REPORT_STATUS_READY_TO_PROCESS,
                    'treated' => 0,
                    'total' => $definition->getCount(),
                    'progress' => '0 / ' . $definition->getCount(),
                    'percentage' => 0,
                    'results_count' => 0,
                    'results' => [],
                    'fixed_count' => 0,
                ];
                $this->writeInCacheAndReturnJson();
                break;
            }

            if (
                isset($this->report['items'][$definition->getKey()])
                && ($current = &$this->report['items'][$definition->getKey()])
                && in_array($current['status'], [Constants::REPORT_STATUS_READY_TO_PROCESS, Constants::REPORT_STATUS_PROCESSING])
            ) {
                $rows = $definition->getRows($current['treated'], Constants::MAX_ELEMENTS_PER_PROCESS);
                list($founded_elements, $founded_count, $fixed_count) = $this->run($definition, $rows, $shouldFix);
                $current['results'] = array_merge_recursive(
                    $current['results'],
                    $founded_elements
                );
                $current['results_count'] += $founded_count;
                $current['fixed_count'] += $fixed_count;
                $current['treated'] += count($rows);
                $current['progress'] = $current['treated'] . ' / ' . $current['total'];
                $current['percentage'] = round(($current['treated'] / $current['total']) * 100, 2);

                if ($current['treated'] >= $current['total'] || count($rows) === 0) {
                    $current['percentage'] = 100;
                    $current['status'] = Constants::REPORT_STATUS_DONE;
                } else {
                    $current['status'] = Constants::REPORT_STATUS_PROCESSING;
                }
                $this->writeInCacheAndReturnJson();
                break;
            }
        }

        $this->writeInCacheAndReturnJson('done');
    }

    private function writeInCacheAndReturnJson($status = Constants::JSON_STATUS_SUCCESS)
    {
        $this->report['duration'] += microtime(true) - $this->start_process_time;
        if ($status === 'done') {
            $this->report['status'] = Constants::REPORT_COMPLETED;
            $this->report['date'] = date('Y-m-d H:i:s');
        }

        try {
            CacheManager::write($this->getKey(true), $this->report);
        } catch (\PrestaShopException $e) {
            $this->returnJsonError($e->getMessage());
        }
        echo json_encode([
            'status' => $status,
            'report' => $this->report,
        ]);
        exit;
    }

    private function returnJsonError($message)
    {
        echo json_encode([
            'status' => Constants::JSON_STATUS_ERROR,
            'message' => $message,
        ]);
        exit;
    }

    /**
     * @throws \Exception
     */
    public function getAllowedFieldsTypes(): array
    {
        throw new \Exception('Method getAllowedFieldsTypes must be implemented');
    }

    public function getDescription(): string
    {
        return 'TODO: manage desc report';
    }
}
