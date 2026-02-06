<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GuestVisits extends MY_Controller
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
        $page = max(1, (int)$this->input->get('page'));
        $per = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        if (!$this->auth_house_id) {
            api_ok(['items' => []], ['page' => $page, 'per_page' => $per, 'total' => 0, 'total_pages' => 0]);
            return;
        }

        $filters = [
            'q' => $this->get_q(),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'house_id' => (int)$this->auth_house_id,
        ];

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
