<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuditLogs extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_permission('app.services.settings.rbac.manage');

        $this->load->model('Audit_log_model', 'AuditModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(200, max(1, (int)$this->input->get('per_page') ?: 30));

        $filters = [
            'user_id' => $this->input->get('user_id') ? (int)$this->input->get('user_id') : null,
            'action'  => $this->input->get('action') ? (string)$this->input->get('action') : null,
            'from'    => $this->input->get('from') ? (string)$this->input->get('from') : null,
            'to'      => $this->input->get('to') ? (string)$this->input->get('to') : null,
            'q'       => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        $err = $this->validate_filters($filters);
        if ($err) { api_validation_error($err); return; }

        $res = $this->AuditModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    private function validate_filters(array $f): array
    {
        $err = [];
        foreach (['from','to'] as $k) {
            if (!empty($f[$k])) {
                if (!preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', (string)$f[$k])) {
                    $err[$k] = 'Format harus YYYY-MM-DD HH:MM:SS';
                }
            }
        }
        return $err;
    }
}
