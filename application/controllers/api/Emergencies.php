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
        $this->require_permission('app.services.security.emergencies.manage');

        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $filters = [
            'q' => $this->_get_q(),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'type' => $this->input->get('type') ? (string)$this->input->get('type') : null,
        ];

        $res = $this->EmergencyModel->paginate($page, $per, $filters);
        api_ok(['items'=>$res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $type = $in['type'] ?? 'other';
        $allowed = ['medical','fire','crime','accident','lost_child','other'];
        if (!in_array($type, $allowed, true)) {
            api_validation_error(['type' => 'Nilai tidak valid']);
            return;
        }

        $payload = $in;
        $payload['type'] = $type;
        $payload['created_by'] = (int)$this->auth_user['id'];
        $payload['reporter_person_id'] = (int)($this->auth_user['person_id'] ?? 0) ?: null;
        if (empty($payload['house_id']) && !empty($this->auth_house_id)) $payload['house_id'] = (int)$this->auth_house_id;

        $id = $this->EmergencyModel->create($payload);
        api_ok($this->EmergencyModel->find_by_id($id), null, 201);
    }

    public function acknowledge(int $id = 0): void
    {
        $this->require_permission('app.services.security.emergencies.manage');
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->EmergencyModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->EmergencyModel->update($id, [
            'status' => 'acknowledged',
            'acknowledged_by' => (int)$this->auth_user['id'],
            'acknowledged_at' => date('Y-m-d H:i:s'),
        ]);
        api_ok($this->EmergencyModel->find_by_id($id));
    }

    public function resolve(int $id = 0): void
    {
        $this->require_permission('app.services.security.emergencies.manage');
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->EmergencyModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $this->EmergencyModel->update($id, [
            'status' => 'resolved',
            'resolved_by' => (int)$this->auth_user['id'],
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolution_note' => $in['resolution_note'] ?? null,
        ]);
        api_ok($this->EmergencyModel->find_by_id($id));
    }

    public function cancel(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->EmergencyModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $can_manage = $this->has_permission('app.services.security.emergencies.manage');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$can_manage && (int)($row['reporter_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $this->EmergencyModel->update($id, ['status'=>'cancelled']);
        api_ok($this->EmergencyModel->find_by_id($id));
    }

    private function _get_q(): ?string
    {
        $q = $this->input->get('q');
        $q = is_string($q) ? trim($q) : '';
        return $q !== '' ? $q : null;
    }
}
