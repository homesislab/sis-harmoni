<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();

        $this->load->model('User_model', 'UserModel');
        $this->load->library('AuthToken');
        $this->load->library('Rbac');
    }

    public function login(): void
    {
        $in = $this->json_input();
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');

        if ($username === '' || $password === '') {
            api_validation_error([
                'username' => $username === '' ? 'Wajib diisi' : null,
                'password' => $password === '' ? 'Wajib diisi' : null,
            ], 'Validasi gagal');
            return;
        }

        $user = $this->UserModel->find_by_username($username);
        if (!$user) {
            api_error('UNAUTHENTICATED', 'Username atau password salah', 401);
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            api_error('ACCOUNT_INACTIVE', 'Akun tidak aktif. Hubungi pengurus.', 403);
            return;
        }

        if (!password_verify($password, $user['password_hash'])) {
            api_error('UNAUTHENTICATED', 'Username atau password salah', 401);
            return;
        }

        $userId = (int)$user['id'];
        $personId = isset($user['person_id']) ? (int)$user['person_id'] : null;

        $rbac = $this->rbac->load_for_user($userId); // expected: ['roles'=>[], 'permissions'=>[]]

        $tokenPayload = [
            'user_id'     => $userId,
            'person_id'   => $personId,
            'roles'       => $rbac['roles'] ?? [],
            'permissions' => $rbac['permissions'] ?? [],
        ];

        $token = $this->authtoken->issue($tokenPayload);

        $payload = $this->UserModel->get_me_payload($userId, $rbac);

        api_ok([
            'token' => $token,
            'me'    => $payload,
        ]);
    }

    public function me(): void
    {
        $this->require_auth();

        $rbac = [
            'roles'       => $this->auth_roles ?? [],
            'permissions' => $this->auth_permissions ?? [],
        ];

        $payload = $this->UserModel->get_me_payload((int)$this->auth_user['id'], $rbac);
        api_ok($payload);
    }

    public function update_me(): void
    {
        $this->require_auth();

        $in = $this->json_input();
        $email = array_key_exists('email', $in) ? trim((string)($in['email'] ?? '')) : null;
        $password = array_key_exists('password', $in) ? (string)($in['password'] ?? '') : null;

        $err = [];
        if ($email !== null) {
            if ($email === '') {
                $err['email'] = 'Tidak boleh kosong';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err['email'] = 'Format email tidak valid';
            } elseif ($this->UserModel->is_email_taken($email, (int)$this->auth_user['id'])) {
                $err['email'] = 'Email sudah digunakan';
            }
        }
        if ($password !== null) {
            if ($password === '') {
                $err['password'] = 'Tidak boleh kosong';
            } elseif (strlen($password) < 6) {
                $err['password'] = 'Minimal 6 karakter';
            }
        }
        if ($err) { api_validation_error($err); return; }

        $upd = [];
        if ($email !== null) $upd['email'] = $email;
        if ($password !== null) $upd['password_hash'] = password_hash($password, PASSWORD_BCRYPT);

        $this->UserModel->update_self((int)$this->auth_user['id'], $upd);

        $rbac = [
            'roles'       => $this->auth_roles ?? [],
            'permissions' => $this->auth_permissions ?? [],
        ];
        $payload = $this->UserModel->get_me_payload((int)$this->auth_user['id'], $rbac);
        api_ok($payload, ['message' => 'Akun diperbarui']);
    }

    public function logout(): void
    {
        $this->require_auth();
        api_ok(null, ['message' => 'Logout berhasil']);
    }
}
