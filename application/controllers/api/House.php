<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class House extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('House_model', 'HouseModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));
        $q    = trim((string)$this->input->get('q'));

        $status = trim((string)$this->input->get('status'));        // occupied|vacant|owned|rented|plot|unknown
        $type   = trim((string)$this->input->get('type'));          // house|kavling
        $status_group = trim((string)$this->input->get('status_group')); // inhabited

        if ($this->has_permission('app.services.master.houses.manage')) {
            $res = $this->HouseModel->paginate($page, $per, $q, $status, $type, $status_group);
        } else {
            if (empty($this->auth_user['person_id'])) {
                api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
                return;
            }
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) { api_error('FORBIDDEN','Akun belum terhubung ke household',403); return; }

            $res = $this->HouseModel->paginate_for_household_active_occupancy(
                $hhid, $page, $per, $q, $status, $type, $status_group
            );
        }

        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.master.houses.manage');

        $in = $this->json_input();
        $err = $this->HouseModel->validate_payload($in, true);
        if ($err) { api_validation_error($err); return; }

        if ($this->HouseModel->exists_code($in['code'])) {
            api_conflict('Kode rumah sudah terpakai');
            return;
        }
        if ($this->HouseModel->exists_block_number($in['block'], $in['number'])) {
            api_conflict('Block+Number sudah terpakai');
            return;
        }

        $id = $this->HouseModel->create($in);
        api_ok($this->HouseModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->HouseModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        if (!$this->has_permission('app.services.master.houses.manage')) {
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) { api_error('FORBIDDEN','Akun belum terhubung ke household',403); return; }

            if (method_exists($this->HouseModel, 'is_house_in_household_active_occupancy')) {
                $ok = $this->HouseModel->is_house_in_household_active_occupancy($id, $hhid);
                if (!$ok) { api_not_found(); return; }
            }
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.master.houses.manage');
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->HouseModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $err = $this->HouseModel->validate_payload($in, false);
        if ($err) { api_validation_error($err); return; }

        if (isset($in['code']) && $this->HouseModel->exists_code($in['code'], $id)) {
            api_conflict('Kode rumah sudah terpakai');
            return;
        }
        if ((isset($in['block']) || isset($in['number'])) && $this->HouseModel->exists_block_number(
            $in['block'] ?? $row['block'],
            $in['number'] ?? $row['number'],
            $id
        )) {
            api_conflict('Block+Number sudah terpakai');
            return;
        }

        $this->HouseModel->update($id, $in);
        api_ok($this->HouseModel->find_by_id($id));
    }
}
