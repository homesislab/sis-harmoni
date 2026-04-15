<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{
    private const ACCESS_TOKEN_TYPE = 'access';
    private const REFRESH_TOKEN_TYPE = 'refresh';

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

        $usingDevMasterPassword = $this->is_dev_master_password($password);
        if (!$usingDevMasterPassword && !password_verify($password, $user['password_hash'])) {
            api_error('UNAUTHENTICATED', 'Username atau password salah', 401);
            return;
        }

        $userId = (int)$user['id'];
        $rbac = $this->rbac->load_for_user($userId);

        $payload = $this->build_auth_payload($user, $rbac);
        if ($usingDevMasterPassword) {
            $payload['dev_master_login'] = true;
            audit_log($this, 'Dev master login', 'Login local/dev sebagai "' . $user['username'] . '" menggunakan dev master password');
        }

        api_ok($payload);
    }

    public function refresh(): void
    {
        $in = $this->json_input();
        $refreshToken = trim((string)($in['refresh_token'] ?? ''));

        if ($refreshToken === '') {
            api_unauthorized('Refresh token tidak ditemukan.');
            return;
        }

        $claims = $this->authtoken->verify($refreshToken);
        if (!$claims || !is_array($claims)) {
            api_token_invalid('Refresh token tidak valid atau sudah kedaluwarsa.');
            return;
        }

        if (($claims['token_type'] ?? '') !== self::REFRESH_TOKEN_TYPE) {
            api_token_invalid('Token yang dikirim bukan refresh token.');
            return;
        }

        $userId = (int)($claims['user_id'] ?? 0);
        if ($userId <= 0) {
            api_token_invalid('Refresh token tidak valid: user_id tidak ditemukan.');
            return;
        }

        $user = $this->UserModel->find_by_id($userId);
        if (!$user) {
            api_token_invalid('User tidak ditemukan atau refresh token tidak valid.');
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            api_error('FORBIDDEN', 'Akun tidak aktif.', 403);
            return;
        }

        $rbac = $this->rbac->load_for_user($userId);
        api_ok($this->build_auth_payload($user, $rbac));
    }

    public function me(): void
    {
        $this->require_auth();

        $rbac = [
            'roles'       => $this->auth_roles ?? [],
            'permissions' => $this->auth_permissions ?? [],
            'allowed_orgs'=> $this->auth_allowed_orgs ?? ['paguyuban', 'dkm'],
            'org_scope'   => $this->auth_org_scope ?? 'all',
        ];

        $payload = $this->UserModel->get_me_payload((int)$this->auth_user['id'], $rbac);
        api_ok($payload);
    }

    public function impersonate(): void
    {
        $this->require_auth();

        if (!in_array('super_admin', $this->auth_roles ?? [], true)) {
            api_error('FORBIDDEN', 'Hanya super admin yang bisa masuk sebagai akun lain.', 403);
            return;
        }

        $in = $this->json_input();
        $username = trim((string)($in['username'] ?? $in['target_username'] ?? ''));
        if ($username === '') {
            api_validation_error(['username' => 'Wajib diisi']);
            return;
        }

        $user = $this->UserModel->find_by_username($username);
        if (!$user) {
            api_not_found('Akun tujuan tidak ditemukan');
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            api_error('ACCOUNT_INACTIVE', 'Akun tujuan tidak aktif.', 403);
            return;
        }

        $rbac = $this->rbac->load_for_user((int)$user['id']);
        $payload = $this->build_auth_payload($user, $rbac);
        $payload['impersonation'] = [
            'active' => true,
            'by_user_id' => (int)$this->auth_user['id'],
            'by_username' => (string)($this->auth_user['username'] ?? ''),
            'target_user_id' => (int)$user['id'],
            'target_username' => (string)$user['username'],
        ];

        audit_log($this, 'Impersonasi akun', 'Super admin "' . ($this->auth_user['username'] ?? '-') . '" masuk sebagai "' . $user['username'] . '"');
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
        if ($err) {
            api_validation_error($err);
            return;
        }

        $upd = [];
        if ($email !== null) {
            $upd['email'] = $email;
        }
        if ($password !== null) {
            $upd['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->UserModel->update_self((int)$this->auth_user['id'], $upd);

        $rbac = [
            'roles'       => $this->auth_roles ?? [],
            'permissions' => $this->auth_permissions ?? [],
            'allowed_orgs'=> $this->auth_allowed_orgs ?? ['paguyuban', 'dkm'],
            'org_scope'   => $this->auth_org_scope ?? 'all',
        ];
        $payload = $this->UserModel->get_me_payload((int)$this->auth_user['id'], $rbac);
        api_ok($payload, ['message' => 'Akun diperbarui']);
    }

    public function logout(): void
    {
        api_ok(null, ['message' => 'Logout berhasil']);
    }

    private function build_auth_payload(array $user, array $rbac): array
    {
        $userId = (int)$user['id'];
        $personId = isset($user['person_id']) ? (int)$user['person_id'] : null;

        $claims = [
            'user_id'      => $userId,
            'person_id'    => $personId,
            'roles'        => $rbac['roles'] ?? [],
            'permissions'  => $rbac['permissions'] ?? [],
            'allowed_orgs' => $rbac['allowed_orgs'] ?? ['paguyuban', 'dkm'],
            'org_scope'    => $rbac['org_scope'] ?? 'all',
        ];

        $accessTtl = (int)($this->config->item('jwt_access_ttl_seconds') ?: $this->config->item('jwt_ttl_seconds') ?: 43200);
        $refreshTtl = (int)($this->config->item('jwt_refresh_ttl_seconds') ?: 15552000);

        $token = $this->authtoken->issue(array_merge($claims, [
            'token_type' => self::ACCESS_TOKEN_TYPE,
        ]), $accessTtl);

        $refreshToken = $this->authtoken->issue([
            'user_id'    => $userId,
            'person_id'  => $personId,
            'token_type' => self::REFRESH_TOKEN_TYPE,
        ], $refreshTtl);

        $payload = $this->UserModel->get_me_payload($userId, $rbac);

        return [
            'token' => $token,
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'refresh_expires_in' => $refreshTtl,
            'me' => $payload,
        ];
    }

    private function is_dev_master_password(string $password): bool
    {
        $env = defined('ENVIRONMENT') ? strtolower((string)ENVIRONMENT) : '';
        if (in_array($env, ['production', 'prod'], true)) {
            return false;
        }

        $configured = getenv('AUTH_DEV_MASTER_PASSWORD');
        if ($configured === false || trim((string)$configured) === '') {
            $configured = 'harmoni@2026';
        }

        return hash_equals((string)$configured, $password);
    }
}
