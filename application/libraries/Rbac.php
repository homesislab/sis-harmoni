<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rbac
{
    private const VALID_ORGS = ['paguyuban', 'dkm'];

    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
        $this->ensure_role_scope_column();
    }

    private function ensure_role_scope_column(): void
    {
        $CI =& get_instance();

        if (!$CI->db->table_exists('roles')) {
            return;
        }

        if ($CI->db->field_exists('org_scope', 'roles')) {
            return;
        }

        try {
            $CI->db->query("ALTER TABLE roles ADD COLUMN org_scope VARCHAR(16) NOT NULL DEFAULT 'all' AFTER description");
        } catch (\Throwable $e) {
            // Best-effort schema sync for older environments.
        }
    }

    private function normalize_scope($scope): string
    {
        $value = strtolower(trim((string)$scope));
        if (in_array($value, self::VALID_ORGS, true)) {
            return $value;
        }
        return 'all';
    }

    private function scopes_to_allowed_orgs(array $scopes): array
    {
        if (empty($scopes)) {
            return self::VALID_ORGS;
        }

        $allowed = [];
        foreach ($scopes as $scope) {
            $norm = $this->normalize_scope($scope);
            if ($norm === 'all') {
                return self::VALID_ORGS;
            }
            $allowed[] = $norm;
        }

        $allowed = array_values(array_unique(array_filter($allowed, function ($item) {
            return in_array($item, self::VALID_ORGS, true);
        })));

        return !empty($allowed) ? $allowed : self::VALID_ORGS;
    }

    /**
     * Return:
     * [
     *   'roles' => ['resident','admin',...],
     *   'permissions' => ['app.services.finance.invoices.manage','payment.verify',...],
     *   'allowed_orgs' => ['paguyuban','dkm'],
     *   'org_scope' => 'all'|'paguyuban'|'dkm',
     * ]
     */
    public function load_for_user(int $user_id): array
    {
        $CI =& get_instance();

        $roleRows = $CI->db
            ->select('r.code, r.org_scope')
            ->from('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id', 'inner')
            ->where('ur.user_id', $user_id)
            ->get()->result_array();

        $role_codes = [];
        $role_scopes = [];
        foreach ($roleRows as $row) {
            $code = trim((string)($row['code'] ?? ''));
            if ($code !== '') {
                $role_codes[] = $code;
            }
            $role_scopes[] = $row['org_scope'] ?? 'all';
        }
        $role_codes = array_values(array_unique($role_codes));

        $perms_role = $CI->db
            ->select('p.code')
            ->from('user_roles ur')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id', 'inner')
            ->join('permissions p', 'p.id = rp.permission_id', 'inner')
            ->where('ur.user_id', $user_id)
            ->get()->result_array();

        $perm_codes = array_map(fn($x) => $x['code'], $perms_role);

        $perms_user = $CI->db
            ->select('p.code')
            ->from('user_permissions up')
            ->join('permissions p', 'p.id = up.permission_id', 'inner')
            ->where('up.user_id', $user_id)
            ->get()->result_array();

        foreach ($perms_user as $row) {
            $perm_codes[] = $row['code'];
        }

        $perm_codes = array_values(array_unique($perm_codes));
        $allowed_orgs = $this->scopes_to_allowed_orgs($role_scopes);
        $org_scope = count($allowed_orgs) === 1 ? $allowed_orgs[0] : 'all';

        return [
            'roles' => $role_codes,
            'permissions' => $perm_codes,
            'allowed_orgs' => $allowed_orgs,
            'org_scope' => $org_scope,
        ];
    }
}
