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

    /** @var array */
    public $auth_allowed_orgs = ['paguyuban', 'dkm'];

    /** @var string */
    public $auth_org_scope = 'all';

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
        $this->load->library('AuthToken');
        $this->load->library('Rbac');
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

    protected function normalize_org_value($value, bool $allow_empty = false): ?string
    {
        $raw = strtolower(trim((string)$value));
        if ($raw === '') {
            return $allow_empty ? null : 'paguyuban';
        }
        if (in_array($raw, ['paguyuban', 'dkm'], true)) {
            return $raw;
        }
        return null;
    }

    protected function allowed_orgs(): array
    {
        $allowed = array_values(array_unique(array_filter(array_map(function ($item) {
            $norm = $this->normalize_org_value($item, true);
            return $norm ?: null;
        }, is_array($this->auth_allowed_orgs) ? $this->auth_allowed_orgs : []))));

        return !empty($allowed) ? $allowed : ['paguyuban', 'dkm'];
    }

    protected function can_access_org($org): bool
    {
        $norm = $this->normalize_org_value($org, true);
        if ($norm === null) {
            return true;
        }
        return in_array($norm, $this->allowed_orgs(), true);
    }

    protected function constrain_org_filter($org): ?string
    {
        $norm = $this->normalize_org_value($org, true);
        $allowed = $this->allowed_orgs();

        if ($norm !== null) {
            if (!in_array($norm, $allowed, true)) {
                api_error('FORBIDDEN', 'Akses ke unit pelayanan ini ditolak.', 403);
                exit;
            }
            return $norm;
        }

        return count($allowed) === 1 ? $allowed[0] : null;
    }

    protected function require_org_access($org, string $message = 'Akses ke unit pelayanan ini ditolak.'): void
    {
        if ($this->can_access_org($org)) {
            return;
        }
        api_error('FORBIDDEN', $message, 403);
        exit;
    }

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
        $this->auth_allowed_orgs = $rbac['allowed_orgs'] ?? ($claims['allowed_orgs'] ?? ['paguyuban', 'dkm']);
        $this->auth_org_scope = $rbac['org_scope'] ?? ($claims['org_scope'] ?? 'all');

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

    protected function optional_auth(): void
    {
        $token = api_bearer_token();
        if (!$token) {
            return;
        }

        $claims = $this->authtoken->verify($token);
        if (!$claims || !is_array($claims)) {
            return;
        }

        $user_id = (int)($claims['user_id'] ?? 0);
        if ($user_id <= 0) {
            return;
        }

        $user = $this->UserModel->find_by_id($user_id);
        if (!$user || ($user['status'] ?? '') !== 'active') {
            return;
        }

        $rbac = $this->rbac->load_for_user($user_id);

        $this->auth_user = $user;
        $this->auth_roles = $rbac['roles'] ?? ($claims['roles'] ?? []);
        $this->auth_permissions = $rbac['permissions'] ?? ($claims['permissions'] ?? []);
        $this->auth_allowed_orgs = $rbac['allowed_orgs'] ?? ($claims['allowed_orgs'] ?? ['paguyuban', 'dkm']);
        $this->auth_org_scope = $rbac['org_scope'] ?? ($claims['org_scope'] ?? 'all');

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

    protected function get_pagination_params(int $default_per_page = 20, int $max_per_page = 100): array
    {
        $page = max(1, (int)$this->input->get('page'));
        $per_page = min($max_per_page, max(1, (int)$this->input->get('per_page') ?: $default_per_page));
        $offset = ($page - 1) * $per_page;

        return [
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset
        ];
    }

    protected function get_search_query(string $param = 'q'): string
    {
        return trim((string)($this->input->get($param) ?? ''));
    }
}
