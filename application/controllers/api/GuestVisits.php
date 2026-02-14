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
        $this->require_permission('app.services.security.guest_book.manage');
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $filters = [
            'q' => $this->_get_q(),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'house_id' => $this->input->get('house_id') ? (int)$this->input->get('house_id') : null,
            'destination_type' => $this->input->get('destination_type') ? (string)$this->input->get('destination_type') : null,
        ];

        $res = $this->GuestVisitModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.security.guest_book.manage');

        $in = $this->json_input();

        $destination_type = strtolower(trim((string)($in['destination_type'] ?? 'unit')));
        if (!in_array($destination_type, ['unit','non_unit'], true)) $destination_type = 'unit';

        $destination_label = isset($in['destination_label']) ? trim((string)$in['destination_label']) : null;

        $raw_house_id = (int)($in['house_id'] ?? 0);
        $house_id = ($raw_house_id > 0 ? $raw_house_id : null);

        $host_person_id = isset($in['host_person_id']) && (int)$in['host_person_id'] > 0
            ? (int)$in['host_person_id']
            : null;

        $visitor_name  = trim((string)($in['visitor_name'] ?? ''));
        $visitor_phone = isset($in['visitor_phone']) ? trim((string)$in['visitor_phone']) : null;
        $purpose       = trim((string)($in['purpose'] ?? ''));
        $visitor_count = isset($in['visitor_count']) ? (int)$in['visitor_count'] : 1;
        $vehicle_plate = isset($in['vehicle_plate']) ? trim((string)$in['vehicle_plate']) : null;
        $note          = isset($in['note']) ? trim((string)$in['note']) : null;

        $visit_at = trim((string)($in['visit_at'] ?? ''));
        if ($visit_at === '') $visit_at = date('Y-m-d H:i:s');

        $err = [];

        if ($destination_type === 'unit') {
            if ($house_id === null) $err['house_id'] = 'Pilih unit tujuan.';
            $destination_label = null; // ignore
        } else {
            $house_id = null;
            if ($destination_label === null || $destination_label === '') {
                $err['destination_label'] = 'Isi tujuan lokasi.';
            }
        }

        if ($visitor_name === '') $err['visitor_name'] = 'Nama tamu wajib diisi.';
        if ($purpose === '') $err['purpose'] = 'Keperluan wajib diisi.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $visit_at)) {
            $err['visit_at'] = 'Format YYYY-MM-DD HH:MM:SS';
        }
        if ($visitor_count <= 0) $err['visitor_count'] = 'Minimal 1 orang.';

        if ($err) { api_validation_error($err); return; }

        $payload = [
            'house_id'           => $house_id,
            'destination_type'   => $destination_type,
            'destination_label'  => ($destination_label !== '' ? $destination_label : null),

            'host_person_id'     => $host_person_id,
            'visitor_name'       => $visitor_name,
            'visitor_phone'      => ($visitor_phone !== '' ? $visitor_phone : null),
            'purpose'            => $purpose,
            'visitor_count'      => $visitor_count,
            'vehicle_plate'      => ($vehicle_plate !== '' ? $vehicle_plate : null),
            'visit_at'           => $visit_at,
            'note'               => ($note !== '' ? $note : null),

            'status'             => 'checked_in',
            'checked_in_at'      => date('Y-m-d H:i:s'),
            'created_by'         => (int)$this->auth_user['id'],
        ];

        $id = $this->GuestVisitModel->create($payload);
        api_ok($this->GuestVisitModel->find_by_id($id), null, 201);
    }

    public function check_out(int $id = 0): void
    {
        $this->require_permission('app.services.security.guest_book.manage');
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->GuestVisitModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->GuestVisitModel->update($id, [
            'status' => 'checked_out',
            'checked_out_at' => date('Y-m-d H:i:s'),
        ]);
        api_ok($this->GuestVisitModel->find_by_id($id));
    }

    private function _get_q(): ?string
    {
        $q = $this->input->get('q');
        $q = is_string($q) ? trim($q) : '';
        return $q !== '' ? $q : null;
    }
}
