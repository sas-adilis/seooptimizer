<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\FrontAudit\FrontPageAnalyzer;

class SeoOptimizerPageauditModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!$this->isEmployeeBrowsing()) {
            $this->returnJson('error', 'Unauthorized');
        }

        if (!(int) Configuration::get('SEOO_FRONT_AUDIT_ENABLED')) {
            $this->returnJson('error', 'Front audit is disabled');
        }

        $url = Tools::getValue('audit_url');
        if (empty($url) || !Validate::isUrl($url)) {
            $this->returnJson('error', 'Invalid URL');
        }

        $analyzer = new FrontPageAnalyzer();
        $result = $analyzer->analyze($url);

        if (isset($result['error'])) {
            $this->returnJson('error', $result['error']);
        }

        $this->context->smarty->assign([
            'seoo_audit' => $result,
            'seoo_module_path' => $this->module->getPathUri(),
        ]);

        $html = $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/hook/front-audit-panel.tpl'
        );

        $this->returnJson('success', '', ['html' => $html, 'score' => $result['score']]);
    }

    /**
     * @return bool
     */
    private function isEmployeeBrowsing(): bool
    {
        $adminCookie = new Cookie('psAdmin');

        return !empty($adminCookie->id_employee);
    }

    /**
     * @param string $status
     * @param string $message
     * @param array $data
     */
    private function returnJson(string $status, string $message = '', array $data = [])
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
