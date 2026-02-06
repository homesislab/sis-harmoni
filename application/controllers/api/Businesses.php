<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Businesses extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Local_business_model', 'BusinessModel');
        $this->load->model('Local_product_model', 'ProductModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $is_admin = in_array('admin', $this->auth_roles, true);
        $pid = (int)($this->auth_user['person_id'] ?? 0);

        $owner_q_raw = $this->input->get('owner_person_id');
        $owner_q = ($owner_q_raw !== null && $owner_q_raw !== '') ? (int)$owner_q_raw : null;

        $filters = [
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'owner_person_id' => $owner_q, // may be null
        ];

        $is_lapak_q = $this->input->get('is_lapak');
        if ($is_lapak_q !== null && $is_lapak_q !== '') $filters['is_lapak'] = (int)$is_lapak_q;
        else $filters['is_lapak'] = null;

        if ($is_admin) {
            $filters['status'] = $this->input->get('status') ? (string)$this->input->get('status') : null;
        } else {
            $is_my = ($owner_q !== null && $owner_q === $pid && $pid > 0);

            if ($is_my) {
                $filters['owner_person_id'] = $pid;
                $filters['status'] = $this->input->get('status') ? (string)$this->input->get('status') : null; // null = all
            } else {
                $filters['owner_person_id'] = null; // ignore foreign owner filter
                $filters['status'] = 'active';
            }
        }

        $res = $this->BusinessModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $err = [];
        if (empty($in['name'])) $err['name'] = 'Wajib diisi';
        if (empty($in['category'])) $err['category'] = 'Wajib diisi';
        if ($err) { api_validation_error($err); return; }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];
        $payload['owner_person_id'] = (int)($this->auth_user['person_id'] ?? 0) ?: null;
        if (empty($payload['house_id']) && !empty($this->auth_house_id)) $payload['house_id'] = (int)$this->auth_house_id;
        if (!in_array('admin', $this->auth_roles, true)) $payload['status'] = 'pending';

        $id = $this->BusinessModel->create($payload);
        api_ok($this->BusinessModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $biz = $this->BusinessModel->find_by_id($id);
        if (!$biz) { api_not_found(); return; }

        $is_admin = in_array('admin', $this->auth_roles, true);
        $pid = (int)($this->auth_user['person_id'] ?? 0);

        if (!$is_admin) {
            $is_owner = ((int)($biz['owner_person_id'] ?? 0) === $pid);
            $is_active = (($biz['status'] ?? '') === 'active');
            if (!$is_active && !$is_owner) {
                api_not_found(); // lebih aman daripada 403 untuk publik
                return;
            }
        }

        api_ok($biz);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $is_admin = in_array('admin', $this->auth_roles, true);
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$is_admin && (int)($row['owner_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $in = $this->json_input();
        $this->BusinessModel->update($id, $in);
        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function approve(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->BusinessModel->update($id, [
            'status' => 'active',
            'approved_by' => (int)$this->auth_user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function reject(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->BusinessModel->update($id, [
            'status' => 'rejected',
            'approved_by' => (int)$this->auth_user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function products(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $biz = $this->BusinessModel->find_by_id($id);
        if (!$biz) { api_not_found(); return; }

        $is_admin = in_array('admin', $this->auth_roles, true);
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        $is_owner = ((int)($biz['owner_person_id'] ?? 0) === $pid);

        if (!$is_admin && !$is_owner && ($biz['status'] ?? '') !== 'active') {
            api_error('FORBIDDEN', 'Lapak belum aktif', 403);
            return;
        }

        $status = (!$is_admin && !$is_owner) ? 'active' : null; // null = all
        $items = $this->ProductModel->list_by_business($id, $status);
        api_ok(['items'=>$items]);
    }
}
