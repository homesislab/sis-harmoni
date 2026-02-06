<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ownerships extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Ownership_model', 'OwnershipModel');
        $this->load->model('Person_model', 'PersonModel');
        $this->load->model('House_model', 'HouseModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        if (in_array('admin', $this->auth_roles, true)) {
            $res = $this->OwnershipModel->paginate($page, $per);
        } else {
            if (empty($this->auth_user['person_id'])) {
                api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
                return;
            }
            $res = $this->OwnershipModel->paginate_for_person((int)$this->auth_user['person_id'], $page, $per);
        }

        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_role(['admin']);

        $in = $this->json_input();
        $house_id = (int)($in['house_id'] ?? 0);
        $person_id = (int)($in['person_id'] ?? 0);
        $start_date = trim((string)($in['start_date'] ?? ''));
        $end_date = isset($in['end_date']) ? trim((string)$in['end_date']) : null;
        $note = isset($in['note']) ? trim((string)$in['note']) : null;

        $err = [];
        if ($house_id <= 0) $err['house_id'] = 'Wajib diisi';
        if ($person_id <= 0) $err['person_id'] = 'Wajib diisi';
        if ($start_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $err['start_date'] = 'Format YYYY-MM-DD';
        if ($end_date !== null && $end_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) $err['end_date'] = 'Format YYYY-MM-DD';
        if ($err) { api_validation_error($err); return; }

        if (!$this->HouseModel->find_by_id($house_id)) { api_validation_error(['house_id' => 'House tidak ditemukan']); return; }
        if (!$this->PersonModel->find_by_id($person_id)) { api_validation_error(['person_id' => 'Person tidak ditemukan']); return; }

        $id = $this->OwnershipModel->create([
            'house_id' => $house_id,
            'person_id' => $person_id,
            'start_date' => $start_date,
            'end_date' => $end_date ?: null,
            'note' => $note,
        ]);

        api_ok($this->OwnershipModel->find_by_id($id), null, 201);
    }
}
