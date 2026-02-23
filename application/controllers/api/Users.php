<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Users extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_permission('app.services.settings.users.manage');

        $this->load->model('User_model', 'UserModel');
        $this->load->library('Rbac');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];
        $q    = $this->get_search_query();

        $offset = ($page - 1) * $per;

        $qb = $this->db->from('users u');
        if ($q !== '') {
            $qb->group_start()
                ->like('u.username', $q)
                ->or_like('u.email', $q)
                ->group_end();
        }
        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('u.id,u.person_id,u.username,u.email,u.status,u.created_at,u.updated_at')
            ->order_by('u.id', 'DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        foreach ($items as &$u) {
            $rbac = $this->rbac->load_for_user((int)$u['id']);
            $u['roles'] = $rbac['roles'] ?? [];
        }

        api_ok(['items' => $items], [
            'page' => $page,
            'per_page' => $per,
            'total' => $total,
        ]);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');
        $person_id = isset($in['person_id']) ? (int)$in['person_id'] : null;
        $status = $in['status'] ?? 'active';

        $err = [];
        if ($username === '') {
            $err['username'] = 'Wajib diisi';
        }
        if (strlen($password) < 6) {
            $err['password'] = 'Minimal 6 karakter';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        if ($this->UserModel->find_by_username($username)) {
            api_conflict('Username sudah digunakan');
            return;
        }

        $id = $this->UserModel->create([
            'person_id' => $person_id,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'email' => $in['email'] ?? null,
            'status' => $status,
        ]);

        $role_codes = $in['role_codes'] ?? ['resident'];
        foreach ((array)$role_codes as $rc) {
            $this->UserModel->assign_role_code($id, (string)$rc);
        }

        $user = $this->UserModel->find_by_id($id);
        unset($user['password_hash']);
        api_ok($user, null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $user = $this->UserModel->find_by_id($id);
        if (!$user) {
            api_not_found();
            return;
        }
        unset($user['password_hash']);
        $rbac = $this->rbac->load_for_user($id);
        api_ok(['user' => $user, 'roles' => $rbac['roles'] ?? [], 'permissions' => $rbac['permissions'] ?? []]);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $user = $this->UserModel->find_by_id($id);
        if (!$user) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $upd = [];
        foreach (['email','status','person_id'] as $f) {
            if (array_key_exists($f, $in)) {
                $upd[$f] = $in[$f];
            }
        }
        if (isset($in['password']) && strlen((string)$in['password']) >= 6) {
            $upd['password_hash'] = password_hash((string)$in['password'], PASSWORD_BCRYPT);
        }
        if ($upd) {
            $this->db->where('id', $id)->update('users', $upd);
        }

        $fresh = $this->UserModel->find_by_id($id);
        unset($fresh['password_hash']);
        api_ok($fresh);
    }

    public function roles(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $user = $this->UserModel->find_by_id($id);
        if (!$user) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $role_codes = $in['role_codes'] ?? [];
        if (!is_array($role_codes)) {
            api_validation_error(['role_codes' => 'Harus array']);
            return;
        }

        $this->db->where('user_id', $id)->delete('user_roles');
        foreach ($role_codes as $rc) {
            $this->UserModel->assign_role_code($id, (string)$rc);
        }

        $rbac = $this->rbac->load_for_user($id);
        api_ok(['user_id' => $id,'roles' => $rbac['roles'] ?? []]);
    }
}
