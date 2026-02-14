<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    /** @var array|null */
    public $auth_user = null;

    /** @var array */
    public $auth_roles = [];

    /** @var array */
    public $auth_permissions = [];

    /** @var int|null (optional convenience) */
    public $auth_house_id = null;

    /** @var int|null (primary) */
    public $auth_household_id = null;

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->helper(['api_response', 'api', 'url']);
        $this->load->model('User_model', 'UserModel');
        $this->load->library('AuthToken'); // $this->authtoken
        $this->load->library('Rbac');      // $this->rbac
    }

    protected function as_api(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    protected function json_input(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    /**
     * Require Bearer token -> set:
     * - $this->auth_user
     * - $this->auth_roles
     * - $this->auth_permissions
     * - $this->auth_household_id
     * - $this->auth_house_id (optional)
     */
    protected function require_auth(): void
    {
        $token = api_bearer_token();
        if (!$token) {
            api_unauthorized('Anda belum login');
            exit;
        }

        $claims = $this->authtoken->verify($token);
        if (!$claims || !is_array($claims)) {
            api_token_invalid();
            exit;
        }

        $user_id = (int)($claims['user_id'] ?? 0);
        if ($user_id <= 0) {
            api_token_invalid('Token tidak valid: user_id tidak ditemukan.');
            exit;
        }

        $user = $this->UserModel->find_by_id($user_id);
        if (!$user) {
            api_token_invalid('User tidak ditemukan atau token tidak valid.');
            exit;
        }

        if (($user['status'] ?? '') !== 'active') {
            api_error('FORBIDDEN', 'Akun tidak aktif.', 403);
            exit;
        }

        $rbac = $this->rbac->load_for_user($user_id);

        $this->auth_user = $user;
        $this->auth_roles = $rbac['roles'] ?? ($claims['roles'] ?? []);
        $this->auth_permissions = $rbac['permissions'] ?? ($claims['permissions'] ?? []);

        $pid = (int)($user['person_id'] ?? 0);
        if ($pid > 0) {
            $hhid = $this->UserModel->resolve_household_id_by_person($pid);
            $this->auth_household_id = $hhid ?: null;

            if ($hhid) {
                $hid = $this->UserModel->resolve_house_id_by_household($hhid);
                $this->auth_house_id = $hid ?: null;
            }
        }
    }

    protected function require_role(array $roles): void
    {
        foreach ($roles as $r) {
            if (in_array($r, $this->auth_roles, true)) return;
        }
        api_role_forbidden('Anda tidak memiliki akses untuk endpoint ini.');
        exit;
    }

    protected function has_permission(string $permission): bool
    {
        $p = trim((string)$permission);
        if ($p === '') return false;
        if (empty($this->auth_permissions) || !is_array($this->auth_permissions)) return false;
        return in_array($p, $this->auth_permissions, true);
    }

    protected function require_permission(string $permission): void
    {
        if ($this->has_permission($permission)) return;
        api_permission_denied($permission);
        exit;
    }

    protected function require_any_permission(array $permissions): void
    {
        foreach ($permissions as $p) {
            if ($this->has_permission($p)) return;
        }
        $first = $permissions[0] ?? '';
        api_permission_denied($first, 'Akses ditolak: permission yang sesuai diperlukan.');
        exit;
    }
}
