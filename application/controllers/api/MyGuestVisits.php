<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MyGuestVisits extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Guest_visit_model', 'GuestVisitModel');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $filters = [
            'q' => $this->get_q(),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
        ];

        if (!empty($this->auth_house_id)) {
            $filters['house_id'] = (int)$this->auth_house_id;
            $res = $this->GuestVisitModel->paginate($page, $per, $filters);
            api_ok(['items' => $res['items']], $res['meta']);
            return;
        }

        $household_id = (int)($this->auth_user['household_id'] ?? 0);
        if ($household_id > 0) {
            $rows = $this->db
                ->select('house_id')
                ->from('occupancies')
                ->where('household_id', $household_id)
                ->where_in('status', ['active','verified']) // sesuaikan kalau status kamu beda
                ->get()
                ->result_array();

            $house_ids = array_values(array_filter(array_map(function ($r) {
                return (int)($r['house_id'] ?? 0);
            }, $rows)));

            if ($house_ids) {
                $filters['house_ids'] = $house_ids;
                $res = $this->GuestVisitModel->paginate($page, $per, $filters);
                api_ok(['items' => $res['items']], $res['meta']);
                return;
            }
        }

        $filters['created_by'] = (int)($this->auth_user['id'] ?? 0);
        $res = $this->GuestVisitModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    private function get_q(): ?string
    {
        $q = $this->input->get('q');
        $q = is_string($q) ? trim($q) : '';
        return $q !== '' ? $q : null;
    }
}
