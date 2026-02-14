<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vehicles extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Vehicle_model', 'VehicleModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $person_id = $this->input->get('person_id');
        $person_id = $person_id !== null ? (int)$person_id : null;

        if ($this->has_permission('app.services.master.vehicles.manage')) {
            $res = $this->VehicleModel->paginate_with_household($page, $per, $person_id);
        } else {
            if (empty($this->auth_user['person_id'])) {
                api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
                return;
            }
            $res = $this->VehicleModel->paginate_with_household(
                $page,
                $per,
                (int)$this->auth_user['person_id']
            );
        }

        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();

        $err = $this->VehicleModel->validate_payload($in, true);
        if ($err) { api_validation_error($err); return; }

        if (!$this->has_permission('app.services.master.vehicles.manage')) {
            if (empty($this->auth_user['person_id'])) {
                api_error('FORBIDDEN', 'Akun tidak terhubung ke data warga', 403);
                return;
            }
            $in['person_id'] = (int)$this->auth_user['person_id'];
        }

        if ($this->VehicleModel->exists_person_plate((int)$in['person_id'], $in['plate_number'])) {
            api_conflict('Plat nomor sudah terdaftar untuk warga ini');
            return;
        }

        $id = $this->VehicleModel->create($in);
        api_ok($this->VehicleModel->find_by_id($id), null, 201);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->VehicleModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        if (!$this->has_permission('app.services.master.vehicles.manage')) {
            if (empty($this->auth_user['person_id']) || (int)$row['person_id'] !== (int)$this->auth_user['person_id']) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
        }

        $in = $this->json_input();
        $err = $this->VehicleModel->validate_payload($in, false);
        if ($err) { api_validation_error($err); return; }

        if (isset($in['plate_number'])) {
            $plate = trim((string)$in['plate_number']);
            if ($plate !== '' && $this->VehicleModel->exists_person_plate((int)$row['person_id'], $plate, $id)) {
                api_conflict('Plat nomor sudah terdaftar untuk warga ini');
                return;
            }
        }

        $this->VehicleModel->update($id, $in);
        api_ok($this->VehicleModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->VehicleModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        if (!$this->has_permission('app.services.master.vehicles.manage')) {
            if (empty($this->auth_user['person_id']) || (int)$row['person_id'] !== (int)$this->auth_user['person_id']) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
        }

        $this->VehicleModel->delete($id);
        api_ok(null, ['message' => 'Vehicle dihapus']);
    }
}
