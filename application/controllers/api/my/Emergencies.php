<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emergencies extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Emergency_report_model', 'EmergencyModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $pid = (int)($this->auth_user['person_id'] ?? 0);

        $filters = [
            'q' => $this->get_q(),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'type' => $this->input->get('type') ? (string)$this->input->get('type') : null,
            'reporter_person_id' => $pid,
        ];

        $res = $this->EmergencyModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    private function get_q(): ?string
    {
        $q = $this->input->get('q');
        $q = is_string($q) ? trim($q) : '';
        return $q !== '' ? $q : null;
    }
}
