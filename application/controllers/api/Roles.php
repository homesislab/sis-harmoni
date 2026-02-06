<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_role(['admin']);
    }

    public function index(): void
    {
        $items = $this->db
            ->select('id,code,name,description,created_at,updated_at')
            ->from('roles')
            ->order_by('code','ASC')
            ->get()->result_array();

        foreach ($items as &$r) {
            $perms = $this->db
                ->select('p.code')
                ->from('role_permissions rp')
                ->join('permissions p','p.id = rp.permission_id','inner')
                ->where('rp.role_id', (int)$r['id'])
                ->order_by('p.code','ASC')
                ->get()->result_array();
            $r['permission_codes'] = array_map(fn($x)=>$x['code'], $perms);
        }

        api_ok(['items' => $items]);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $code = trim((string)($in['code'] ?? ''));
        $name = trim((string)($in['name'] ?? ''));

        $err = [];
        if ($code === '') $err['code'] = 'Wajib diisi';
        if ($name === '') $err['name'] = 'Wajib diisi';
        if ($err) { api_validation_error($err); return; }

        $exists = $this->db->get_where('roles', ['code'=>$code])->row_array();
        if ($exists) { api_conflict('Role sudah ada'); return; }

        $this->db->insert('roles', [
            'code' => $code,
            'name' => $name,
            'description' => $in['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $id = (int)$this->db->insert_id();
        $role = $this->db->get_where('roles', ['id'=>$id])->row_array();
        api_ok(['role' => $role], null, 201);
    }

    public function show(int $id=0): void
    {
        if ($id<=0) { api_not_found(); return; }
        $role = $this->db->get_where('roles', ['id'=>$id])->row_array();
        if (!$role) { api_not_found(); return; }

        $perms = $this->db
            ->select('p.code')
            ->from('role_permissions rp')
            ->join('permissions p','p.id = rp.permission_id','inner')
            ->where('rp.role_id', (int)$id)
            ->order_by('p.code','ASC')
            ->get()->result_array();
        $role['permission_codes'] = array_map(fn($x)=>$x['code'], $perms);

        api_ok(['role' => $role]);
    }

    public function update(int $id=0): void
    {
        if ($id<=0) { api_not_found(); return; }
        $role = $this->db->get_where('roles', ['id'=>$id])->row_array();
        if (!$role) { api_not_found(); return; }

        $in = $this->json_input();
        $upd = [];
        foreach (['name','description'] as $f) {
            if (array_key_exists($f, $in)) $upd[$f] = $in[$f];
        }
        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id',$id)->update('roles', $upd);
        }
        $fresh = $this->db->get_where('roles', ['id'=>$id])->row_array();
        api_ok(['role' => $fresh]);
    }

    public function set_permissions(int $id=0): void
    {
        if ($id<=0) { api_not_found(); return; }
        $role = $this->db->get_where('roles', ['id'=>$id])->row_array();
        if (!$role) { api_not_found(); return; }

        $in = $this->json_input();
        $codes = $in['permission_codes'] ?? [];
        if (!is_array($codes)) {
            api_validation_error(['permission_codes' => 'Harus berupa array']);
            return;
        }

        $this->db->where('role_id', $id)->delete('role_permissions');

        foreach ($codes as $c) {
            $code = trim((string)$c);
            if ($code === '') continue;
            $perm = $this->db->get_where('permissions', ['code'=>$code])->row_array();
            if (!$perm) continue;
            $this->db->insert('role_permissions', [
                'role_id' => $id,
                'permission_id' => (int)$perm['id'],
            ]);
        }

        api_ok(['role_id' => $id, 'permission_codes' => array_values($codes)]);
    }

    public function destroy(int $id=0): void
    {
        if ($id<=0) { api_not_found(); return; }
        $role = $this->db->get_where('roles', ['id'=>$id])->row_array();
        if (!$role) { api_not_found(); return; }

        // block delete when role is assigned
        $used = (int)$this->db->from('user_roles')->where('role_id',$id)->count_all_results();
        if ($used > 0) {
            api_conflict('Role masih digunakan oleh user');
            return;
        }

        $this->db->where('id',$id)->delete('roles');
        api_ok(['deleted' => true, 'id' => $id]);
    }
}
