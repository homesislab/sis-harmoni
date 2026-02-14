<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Households extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Household_model', 'HouseholdModel');
        $this->load->model('Person_model', 'PersonModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));
        $q    = trim((string)$this->input->get('q'));

        if ($this->has_permission('app.services.master.households.manage')) {
            $res = $this->HouseholdModel->paginate($page, $per, $q);
        } else {
            if (empty($this->auth_user['person_id'])) {
                api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
                return;
            }
            $res = $this->HouseholdModel->paginate_for_person((int)$this->auth_user['person_id'], $page, $per, $q);
        }

        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.master.households.manage');

        $in = $this->json_input();
        $kk_number = trim((string)($in['kk_number'] ?? ''));
        $head_person_id = (int)($in['head_person_id'] ?? 0);

        $err = [];
        if ($kk_number === '') $err['kk_number'] = 'Wajib diisi';
        if ($head_person_id <= 0) $err['head_person_id'] = 'Wajib diisi';
        if ($err) { api_validation_error($err); return; }

        if ($this->HouseholdModel->find_by_kk($kk_number)) {
            api_conflict('No KK sudah terdaftar');
            return;
        }

        if (!$this->PersonModel->find_by_id($head_person_id)) {
            api_validation_error(['head_person_id' => 'Person tidak ditemukan']);
            return;
        }

        $id = $this->HouseholdModel->create([
            'kk_number' => $kk_number,
            'head_person_id' => $head_person_id,
        ]);

        api_ok($this->HouseholdModel->find_detail($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->HouseholdModel->find_detail($id);
        if (!$row) { api_not_found(); return; }

        if (!$this->has_permission('app.services.master.households.manage')) {
            if (empty($this->auth_user['person_id'])) {
                api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
                return;
            }
            $allowed = $this->HouseholdModel->person_is_member((int)$this->auth_user['person_id'], $id);
            if (!$allowed) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.master.households.manage');
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->HouseholdModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $payload = [];

        if (isset($in['kk_number'])) {
            $kk = trim((string)$in['kk_number']);
            if ($kk === '') { api_validation_error(['kk_number' => 'Wajib diisi']); return; }
            $other = $this->HouseholdModel->find_by_kk($kk);
            if ($other && (int)$other['id'] !== $id) { api_conflict('No KK sudah terdaftar'); return; }
            $payload['kk_number'] = $kk;
        }

        if (isset($in['head_person_id'])) {
            $hp = (int)$in['head_person_id'];
            if ($hp <= 0) { api_validation_error(['head_person_id' => 'Wajib diisi']); return; }
            if (!$this->PersonModel->find_by_id($hp)) { api_validation_error(['head_person_id' => 'Person tidak ditemukan']); return; }
            $payload['head_person_id'] = $hp;
        }

        if (!$payload) { api_ok($this->HouseholdModel->find_detail($id)); return; }

        $this->HouseholdModel->update($id, $payload);
        api_ok($this->HouseholdModel->find_detail($id));
    }
}
