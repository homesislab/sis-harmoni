<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Persons extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Person_model', 'PersonModel');
    }

    public function index(): void
    {
        $this->require_permission('app.services.master.residents.manage');

        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));
        $q    = trim((string)$this->input->get('q'));

        $res = $this->PersonModel->paginate($page, $per, $q);

        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.master.residents.manage');

        $in = $this->json_input();

        $errors = $this->PersonModel->validate_payload($in, true);
        if ($errors) { api_validation_error($errors); return; }

        if ($this->PersonModel->find_by_nik(trim((string)$in['nik']))) {
            api_conflict('NIK sudah terdaftar');
            return;
        }

        $id = $this->PersonModel->create($in);
        api_ok($this->PersonModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        if ($this->has_permission('app.services.master.residents.manage')) {
            $row = $this->PersonModel->find_by_id($id);
        } else {
            if (empty($this->auth_user['person_id']) || (int)$this->auth_user['person_id'] !== $id) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
            $row = $this->PersonModel->find_by_id($id);
        }

        if (!$row) { api_not_found(); return; }
        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $is_admin = $this->has_permission('app.services.master.residents.manage');
        if (!$is_admin) {
            if (empty($this->auth_user['person_id']) || (int)$this->auth_user['person_id'] !== $id) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
        }

        $row = $this->PersonModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();

        $errors = $this->PersonModel->validate_payload($in, false);
        if ($errors) { api_validation_error($errors); return; }

        if (isset($in['nik'])) {
            $nik = trim((string)$in['nik']);
            $other = $this->PersonModel->find_by_nik($nik);
            if ($other && (int)$other['id'] !== $id) {
                api_conflict('NIK sudah terdaftar');
                return;
            }
        }

        $this->PersonModel->update($id, $in);
        api_ok($this->PersonModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_permission('app.services.master.residents.manage');

        if ($id <= 0) { api_not_found(); return; }
        $row = $this->PersonModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->PersonModel->soft_delete($id);
        api_ok(null, ['message' => 'Person dinonaktifkan (status=left)']);
    }

    public function me(): void
    {
        $person_id = (int)($this->auth_user['person_id'] ?? 0);
        if ($person_id <= 0) { api_not_found(); return; }

        $row = $this->PersonModel->find_by_id($person_id);
        if (!$row) { api_not_found(); return; }

        api_ok($row);
    }
}
