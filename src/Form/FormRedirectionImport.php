<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Utils;

class FormRedirectionImport extends FormAbstract implements FormInterface
{
    const SEPARATOR_COMMA = ',';
    const SEPARATOR_SEMICOLON = ';';

    public function getContent(): string
    {
        return $this->renderForm(
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Import redirections'),
                        'icon' => 'icon-upload',
                    ],
                    'input' => [
                        [
                            'type' => 'file',
                            'name' => 'file',
                            'label' => $this->l('Select your CSV file to import'),
                            'required' => true,
                        ],
                        [
                            'type' => 'select',
                            'name' => 'separator',
                            'label' => $this->l('Select the separator'),
                            'required' => true,
                            'options' => [
                                'default' => [
                                    'value' => null,
                                    'label' => $this->l('Pick an option'),
                                ],
                                'query' => [
                                    [
                                        'value' => self::SEPARATOR_COMMA,
                                        'label' => $this->l('Comma'),
                                    ],
                                    [
                                        'value' => self::SEPARATOR_SEMICOLON,
                                        'label' => $this->l('Semicolon'),
                                    ],
                                ],
                                'id' => 'value',
                                'name' => 'label',
                            ],
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Import'),
                        'name' => 'submit' . $this->getKey(),
                    ],
                ],
            ], [
                'separator' => \Tools::getValue('separator', self::SEPARATOR_SEMICOLON),
            ]
        );
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        $context = \Context::getContext();
        $separator = \Tools::getValue('separator');
        $csv_file = $_FILES['file'];

        if (!is_array($csv_file) || empty($csv_file['tmp_name'])) {
            $context->controller->errors[] = $this->l('Please select a file to import');

            return;
        }

        if ($csv_file['error'] !== UPLOAD_ERR_OK) {
            $context->controller->errors[] = $this->l('An error occurred while uploading the file');

            return;
        }

        $mime = mime_content_type($csv_file['tmp_name']);
        $extension = \Tools::strtolower(pathinfo($csv_file['name'], PATHINFO_EXTENSION));

        if (($mime !== 'text/csv' && $mime !== 'text/plain') || $extension !== 'csv') {
            $context->controller->errors[] = $this->l('Please select a CSV file');

            return;
        }

        $file = fopen($csv_file['tmp_name'], 'r');
        if (!$file) {
            $context->controller->errors[] = $this->l('An error occurred while opening the file');

            return;
        }

        $datas_to_insert = [];
        $have_one_line = false;
        $current_line = 0;
        while (($line = fgetcsv($file, 0, $separator)) !== false) {
            ++$current_line;

            if (!count($line)) {
                continue;
            }

            $have_one_line = true;

            if (!Utils::isRelativeUrl($line[0])) {
                $context->controller->errors[] = sprintf(
                    $this->l('Line %S : source URL is invalid, please use a relative URL without the domain name'),
                    $current_line
                );
                continue;
            }

            if (substr($line[0], 0, 1) !== '/') {
                $line[0] = '/' . $line[0];
            }

            if (!\Validate::isUrl($line[1])) {
                $context->controller->errors[] = sprintf(
                    $this->l('Line %S : Destination URL is invalid'),
                    $current_line
                );
                continue;
            }

            if ((int) $line[2] !== 301 && (int) $line[2] !== 302) {
                $context->controller->errors[] = sprintf(
                    $this->l('Line %S : Invalid redirection type'),
                    $current_line
                );
                continue;
            }

            $datas_to_insert[] = [
                'redirect_from' => $line[0],
                'redirect_to' => $line[1],
                'redirect_type' => $line[2],
                'id_shop' => $context->shop->id,
            ];
        }

        fclose($file);

        if (!$have_one_line) {
            $context->controller->errors[] = $this->l('Invalid CSV file, have you selected the right separator?');

            return;
        }

        $date_now = date('Y-m-d H:i:s');
        foreach (array_chunk($datas_to_insert, 100) as $chunk) {
            $query = '
                INSERT INTO ' . _DB_PREFIX_ . 'seooptimizer_redirect
                (`redirect_from`, `redirect_to`, `redirect_type`, `id_shop`, `date_add`) VALUES ';

            foreach ($chunk as $data) {
                $query .= sprintf(
                    '("%s", "%s", %d, %d, "%s"), ',
                    pSQL($data['redirect_from']),
                    pSQL($data['redirect_to']),
                    (int) $data['redirect_type'],
                    (int) $data['id_shop'],
                    pSQL($date_now)
                );
            }

            $query = rtrim($query, ', ');
            $query .= ' ON DUPLICATE KEY UPDATE `redirect_to` = VALUES(`redirect_to`), `redirect_type` = VALUES(`redirect_type`), `date_upd` = "' . pSQL($date_now) . '";';

            if (!\Db::getInstance()->execute($query)) {
                $context->controller->errors[] = $this->l('An error occurred while inserting the data');

                return;
            }
        }

        if (count($context->controller->errors)) {
            \Context::getContext()->smarty->assign('show_' . $this->getKey(true), true);
        } else {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(18));
        }
    }
}
