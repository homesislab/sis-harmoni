<?php

defined('BASEPATH') or exit('No direct script access allowed');

class OrgUnits extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_permission('app.home.dashboard');
    }

    public function index(): void
    {
        $table = null;
        if ($this->db->table_exists('org_unit')) {
            $table = 'org_unit';
        }
        if ($table === null && $this->db->table_exists('org_units')) {
            $table = 'org_units';
        }

        if ($table === null) {
            api_ok([]);
            return;
        }

        $fields = ['id', 'name', 'code', 'type', 'status', 'created_at'];
        $cols = [];
        foreach ($fields as $f) {
            if ($this->db->field_exists($f, $table)) {
                $cols[] = $f;
            }
        }

        $q = $this->db;
        if (count($cols) > 0) {
            $q = $q->select(implode(',', $cols));
        }

        $rows = $q->from($table)
            ->order_by($this->db->field_exists('name', $table) ? 'name' : 'id', 'asc')
            ->get()->result_array();

        api_ok($rows);
    }
}
