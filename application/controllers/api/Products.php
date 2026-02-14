<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Local_product_model', 'ProductModel');
        $this->load->model('Local_business_model', 'BusinessModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 40));

        $filters = [
            'business_id' => $this->input->get('business_id') ? (int)$this->input->get('business_id') : null,
            'status'      => $this->input->get('status') ? (string)$this->input->get('status') : 'active',
        ];

        $res = $this->ProductModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $err = [];
        if (empty($in['business_id'])) $err['business_id'] = 'Wajib diisi';
        if (empty($in['name'])) $err['name'] = 'Wajib diisi';
        if ($err) { api_validation_error($err); return; }

        $biz = $this->BusinessModel->find_by_id((int)$in['business_id']);
        if (!$biz) { api_validation_error(['business_id' => 'Business tidak ditemukan']); return; }

        $can_manage = $this->has_permission('app.services.notes.inventories.manage');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$can_manage && (int)($biz['owner_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];

        $id = $this->ProductModel->create($payload);
        api_ok($this->ProductModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->ProductModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ProductModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $biz = $this->BusinessModel->find_by_id((int)$row['business_id']);
        if (!$biz) { api_not_found(); return; }

        $can_manage = $this->has_permission('app.services.notes.inventories.manage');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$can_manage && (int)($biz['owner_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $in = $this->json_input();
        $this->ProductModel->update($id, $in);
        api_ok($this->ProductModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ProductModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $biz = $this->BusinessModel->find_by_id((int)$row['business_id']);
        if (!$biz) { api_not_found(); return; }

        $can_manage = $this->has_permission('app.services.notes.inventories.manage');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$can_manage && (int)($biz['owner_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $this->ProductModel->delete($id);
        api_ok(null, ['message' => 'Produk dihapus']);
    }
}
