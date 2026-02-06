<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ChargeTypes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Charge_model', 'ChargeModel');
    }

    public function index(): void
    {
        $items = $this->ChargeModel->list_types((string)$this->input->get('category'), $this->input->get('active'));
        api_ok(['items' => $items]);
    }

    public function store(): void
    {
        $this->require_any_permission(['billing.manage']);

        $in = $this->json_input();
        $err = $this->ChargeModel->validate_type($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->ChargeModel->create_type($in);
        api_ok($this->ChargeModel->find_type($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->ChargeModel->find_type($id);
        if (!$row) {
            api_not_found();
            return;
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['billing.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->ChargeModel->find_type($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $err = $this->ChargeModel->validate_type($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $this->ChargeModel->update_type($id, $in);
        api_ok($this->ChargeModel->find_type($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['billing.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->ChargeModel->find_type($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->ChargeModel->update_type($id, ['is_active' => 0]);
        api_ok(null, ['message' => 'Jenis iuran dinonaktifkan']);
    }
}
