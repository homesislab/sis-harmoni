<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permissions extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_permission('app.services.settings.rbac.manage');
    }

    public function index(): void
    {
        $items = $this->db
            ->select('id,code,name,description,created_at,updated_at')
            ->from('permissions')
            ->order_by('code','ASC')
            ->get()->result_array();

        api_ok(['items' => $items]);
    }
}
