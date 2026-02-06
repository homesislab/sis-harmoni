<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ImportantContacts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Important_contact_model', 'ContactModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $is_admin = in_array('admin', $this->auth_roles, true);

        $filters = [
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'is_public' => $is_admin ? null : 1,
        ];

        $res = $this->ContactModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_role(['admin']);
        $in = $this->json_input();
        $err = [];
        if (empty($in['name'])) $err['name'] = 'Wajib diisi';
        if ($err) { api_validation_error($err); return; }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];

        $id = $this->ContactModel->create($payload);
        api_ok($this->ContactModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ContactModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        if (!in_array('admin', $this->auth_roles, true) && (int)($row['is_public'] ?? 1) !== 1) {
            api_not_found();
            return;
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ContactModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $this->ContactModel->update($id, $in);

        api_ok($this->ContactModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ContactModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->ContactModel->delete($id);
        api_ok(['ok' => true]);
    }
}
