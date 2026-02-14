<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Events extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Event_model', 'EventModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $filters = [
            'org'  => $this->input->get('org') ? (string)$this->input->get('org') : null,
            'from' => $this->input->get('from') ? (string)$this->input->get('from') : null,
            'to'   => $this->input->get('to') ? (string)$this->input->get('to') : null,
            'q'    => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        $err = $this->EventModel->validate_filters($filters);
        if ($err) { api_validation_error($err); return; }

        $res = $this->EventModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.services.info.events.manage']);

        $in = $this->json_input();
        $err = $this->EventModel->validate_payload($in, true);
        if ($err) { api_validation_error($err); return; }

        $id = $this->EventModel->create($in, (int)$this->auth_user['id']);

        $title = trim((string)($in['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Menambahkan kegiatan', 'Menambahkan kegiatan baru "' . $title . '"');

        api_ok($this->EventModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->EventModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }
        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.events.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->EventModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $err = $this->EventModel->validate_payload($in, false);
        if ($err) { api_validation_error($err); return; }

        $this->EventModel->update($id, $in);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Memperbarui kegiatan', 'Memperbarui kegiatan "' . $title . '"');

        api_ok($this->EventModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.events.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->EventModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->EventModel->delete($id);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Menghapus kegiatan', 'Menghapus kegiatan "' . $title . '"');

        api_ok(null, ['message' => 'Event dihapus']);
    }
}
