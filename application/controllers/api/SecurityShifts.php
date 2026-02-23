<?php

defined('BASEPATH') or exit('No direct script access allowed');

class SecurityShifts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Security_shift_model', 'ShiftModel');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];
        $q    = $this->get_search_query();

        $res = $this->ShiftModel->get_list($page, $per, $q);

        api_ok(['items' => $res['items']], [
            'page' => $page,
            'per_page' => $per,
            'total' => $res['total'],
        ]);
    }

    public function store(): void
    {
        $in = $this->json_input();

        $name = trim((string)($in['name'] ?? ''));
        $start_time = trim((string)($in['start_time'] ?? ''));
        $end_time = trim((string)($in['end_time'] ?? ''));
        $description = isset($in['description']) ? trim((string)$in['description']) : null;

        $err = [];
        if ($name === '') {
            $err['name'] = 'Nama shift wajib diisi';
        }
        if ($start_time === '') {
            $err['start_time'] = 'Waktu mulai wajib diisi';
        }
        if ($end_time === '') {
            $err['end_time'] = 'Waktu selesai wajib diisi';
        }

        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->ShiftModel->create([
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $shift = $this->ShiftModel->find_by_id($id);
        api_ok($shift, null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $shift = $this->ShiftModel->find_by_id($id);
        if (!$shift) {
            api_not_found();
            return;
        }

        api_ok($shift);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $shift = $this->ShiftModel->find_by_id($id);
        if (!$shift) {
            api_not_found();
            return;
        }

        $in = $this->json_input();

        $upd = [];
        if (isset($in['name'])) {
            $name = trim((string)$in['name']);
            if ($name === '') {
                api_validation_error(['name' => 'Nama shift tidak boleh kosong']);
                return;
            }
            $upd['name'] = $name;
        }
        if (isset($in['start_time'])) {
            $start_time = trim((string)$in['start_time']);
            if ($start_time === '') {
                api_validation_error(['start_time' => 'Waktu mulai tidak boleh kosong']);
                return;
            }
            $upd['start_time'] = $start_time;
        }
        if (isset($in['end_time'])) {
            $end_time = trim((string)$in['end_time']);
            if ($end_time === '') {
                api_validation_error(['end_time' => 'Waktu selesai tidak boleh kosong']);
                return;
            }
            $upd['end_time'] = $end_time;
        }
        if (array_key_exists('description', $in)) {
            $upd['description'] = $in['description'] === null ? null : trim((string)$in['description']);
        }

        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->ShiftModel->update($id, $upd);
        }

        $fresh = $this->ShiftModel->find_by_id($id);
        api_ok($fresh);
    }

    public function destroy(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $shift = $this->ShiftModel->find_by_id($id);
        if (!$shift) {
            api_not_found();
            return;
        }

        $this->ShiftModel->delete($id);
        api_ok(['deleted' => true]);
    }
}
