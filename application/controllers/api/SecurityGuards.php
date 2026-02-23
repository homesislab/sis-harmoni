<?php

defined('BASEPATH') or exit('No direct script access allowed');

class SecurityGuards extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        // Option to verify if the user has appropriate permissions
        // $this->require_permission('app.services.security.guards.manage');

        $this->load->model('Security_guard_model', 'GuardModel');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];
        $q    = $this->get_search_query();

        $res = $this->GuardModel->get_list($page, $per, $q);

        api_ok(['items' => $res['items']], [
            'page' => $page,
            'per_page' => $per,
            'total' => $res['total'],
        ]);
    }

    public function store(): void
    {
        $in = $this->json_input();

        $full_name = trim((string)($in['full_name'] ?? ''));
        $phone = trim((string)($in['phone'] ?? ''));
        $user_id = isset($in['user_id']) ? (int)$in['user_id'] : null;
        $employee_id = trim((string)($in['employee_id'] ?? ''));
        $status = in_array($in['status'] ?? '', ['active', 'inactive']) ? $in['status'] : 'active';

        $err = [];
        if ($full_name === '') {
            $err['full_name'] = 'Nama lengkap wajib diisi';
        }

        if ($err) {
            api_validation_error($err);
            return;
        }

        if ($employee_id === '') {
            // Auto generate if not provided
            $employee_id = 'SEC-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }

        if ($this->GuardModel->is_employee_id_taken($employee_id)) {
            api_validation_error(['employee_id' => 'Employee ID sudah digunakan']);
            return;
        }

        $id = $this->GuardModel->create([
            'full_name' => $full_name,
            'phone' => $phone !== '' ? $phone : null,
            'user_id' => $user_id > 0 ? $user_id : null,
            'employee_id' => $employee_id,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $guard = $this->GuardModel->find_by_id($id);
        api_ok($guard, null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $guard = $this->GuardModel->find_by_id($id);
        if (!$guard) {
            api_not_found();
            return;
        }

        api_ok($guard);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $guard = $this->GuardModel->find_by_id($id);
        if (!$guard) {
            api_not_found();
            return;
        }

        $in = $this->json_input();

        $upd = [];
        if (isset($in['full_name'])) {
            $full_name = trim((string)$in['full_name']);
            if ($full_name === '') {
                api_validation_error(['full_name' => 'Nama lengkap tidak boleh kosong']);
                return;
            }
            $upd['full_name'] = $full_name;
        }
        if (array_key_exists('phone', $in)) {
            $phone = trim((string)$in['phone']);
            $upd['phone'] = $phone !== '' ? $phone : null;
        }
        if (array_key_exists('user_id', $in)) {
            $upd['user_id'] = (int)$in['user_id'] > 0 ? (int)$in['user_id'] : null;
        }
        if (isset($in['employee_id'])) {
            $employee_id = trim((string)$in['employee_id']);
            if ($employee_id === '') {
                api_validation_error(['employee_id' => 'Employee ID tidak boleh kosong']);
                return;
            }
            if ($this->GuardModel->is_employee_id_taken($employee_id, $id)) {
                api_validation_error(['employee_id' => 'Employee ID sudah digunakan']);
                return;
            }
            $upd['employee_id'] = $employee_id;
        }
        if (isset($in['status']) && in_array($in['status'], ['active', 'inactive'])) {
            $upd['status'] = $in['status'];
        }

        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->GuardModel->update($id, $upd);
        }

        $fresh = $this->GuardModel->find_by_id($id);
        api_ok($fresh);
    }

    public function destroy(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $guard = $this->GuardModel->find_by_id($id);
        if (!$guard) {
            api_not_found();
            return;
        }

        $this->GuardModel->delete($id);
        api_ok(['deleted' => true]);
    }
}
