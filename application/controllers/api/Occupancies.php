<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Occupancies extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Occupancy_model', 'OccupancyModel');
        $this->load->model('Household_model', 'HouseholdModel');
        $this->load->model('House_model', 'HouseModel');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        if ($this->has_permission('app.services.master.houses.manage')) {
            $res = $this->OccupancyModel->paginate($page, $per);
        } else {
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) {
                api_error('FORBIDDEN', 'Akun belum terhubung ke household', 403);
                return;
            }
            $res = $this->OccupancyModel->paginate_for_household($hhid, $page, $per);
        }

        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.master.houses.manage');

        $in = $this->json_input();
        $house_id = (int)($in['house_id'] ?? 0);
        $household_id = (int)($in['household_id'] ?? 0);
        $occupancy_type = trim((string)($in['occupancy_type'] ?? ''));
        $start_date = trim((string)($in['start_date'] ?? ''));

        $allowed = ['owner_live','owner_not_live','tenant','family','caretaker'];

        $err = [];
        if ($house_id <= 0) {
            $err['house_id'] = 'Wajib diisi';
        }
        if ($household_id <= 0) {
            $err['household_id'] = 'Wajib diisi';
        }
        if (!in_array($occupancy_type, $allowed, true)) {
            $err['occupancy_type'] = 'Nilai tidak valid';
        }
        if ($start_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            $err['start_date'] = 'Format YYYY-MM-DD';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        if (!$this->HouseModel->find_by_id($house_id)) {
            api_validation_error(['house_id' => 'House tidak ditemukan']);
            return;
        }
        if (!$this->HouseholdModel->find_by_id($household_id)) {
            api_validation_error(['household_id' => 'Household tidak ditemukan']);
            return;
        }

        $id = $this->OccupancyModel->create([
            'house_id' => $house_id,
            'household_id' => $household_id,
            'occupancy_type' => $occupancy_type,
            'start_date' => $start_date,
            'note' => $in['note'] ?? null,
        ]);

        api_ok($this->OccupancyModel->find_by_id($id), null, 201);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.master.houses.manage');

        if ($id <= 0) {
            api_validation_error(['id' => 'Wajib diisi (gunakan /occupancies/{id})']);
            return;
        }

        $row = $this->OccupancyModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $payload = [];

        if (isset($in['end_date'])) {
            $ed = trim((string)$in['end_date']);
            if ($ed !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ed)) {
                api_validation_error(['end_date' => 'Format YYYY-MM-DD']);
                return;
            }
            $payload['end_date'] = $ed ?: null;
        }

        if (isset($in['status'])) {
            $st = trim((string)$in['status']);
            if (!in_array($st, ['active','ended'], true)) {
                api_validation_error(['status' => 'Nilai tidak valid']);
                return;
            }
            $payload['status'] = $st;
        }

        if (array_key_exists('note', $in)) {
            $payload['note'] = $in['note'] !== null ? trim((string)$in['note']) : null;
        }

        if (!$payload) {
            api_validation_error(['payload' => 'Tidak ada field yang diupdate']);
            return;
        }

        $this->OccupancyModel->update($id, $payload);
        api_ok($this->OccupancyModel->find_by_id($id));
    }
}
