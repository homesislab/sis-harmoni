<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rbac
{
    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
    }

    /**
     * Return:
     * [
     *   'roles' => ['resident','admin',...],
     *   'permissions' => ['billing.read','payment.verify',...]
     * ]
     */
    public function load_for_user(int $user_id): array
    {
        $CI =& get_instance();

        // Roles
        $roles = $CI->db
            ->select('r.code')
            ->from('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id', 'inner')
            ->where('ur.user_id', $user_id)
            ->get()->result_array();

        $role_codes = array_values(array_unique(array_map(fn($x) => $x['code'], $roles)));

        // Permissions from role_permissions
        $perms_role = $CI->db
            ->select('p.code')
            ->from('user_roles ur')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id', 'inner')
            ->join('permissions p', 'p.id = rp.permission_id', 'inner')
            ->where('ur.user_id', $user_id)
            ->get()->result_array();

        $perm_codes = array_map(fn($x) => $x['code'], $perms_role);

        // Direct grants from user_permissions (grant-only)
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

        return [
            'roles' => $role_codes,
            'permissions' => $perm_codes,
        ];
    }
}
