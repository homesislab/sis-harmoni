<?php

defined('BASEPATH') or exit('No direct script access allowed');

class ChargeComponents extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Charge_model', 'ChargeModel');
    }

    private function require_charge_type_access(int $charge_type_id): array
    {
        $chargeType = $this->ChargeModel->find_type($charge_type_id);
        if (!$chargeType) {
            api_validation_error(['charge_type_id' => 'Charge type tidak ditemukan']);
            exit;
        }

        $this->require_org_access($chargeType['category'] ?? null);
        return $chargeType;
    }

    public function index(): void
    {
        $charge_type_id = (int)($this->input->get('charge_type_id') ?? 0);
        if ($charge_type_id <= 0) {
            api_validation_error(['charge_type_id' => 'Wajib diisi']);
            return;
        }

        $this->require_charge_type_access($charge_type_id);
        $items = $this->ChargeModel->list_components($charge_type_id);
        api_ok(['items' => $items]);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.services.finance.charge_types.manage']);

        $in = $this->json_input();
        $this->require_charge_type_access((int)($in['charge_type_id'] ?? 0));

        $err = $this->ChargeModel->validate_component($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->ChargeModel->create_component($in);
        api_ok($this->ChargeModel->find_component($id), null, 201);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.charge_types.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->ChargeModel->find_component($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->require_charge_type_access((int)$row['charge_type_id']);

        $in = $this->json_input();
        $in['id'] = $id;
        $err = $this->ChargeModel->validate_component($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $this->ChargeModel->update_component($id, $in);
        api_ok($this->ChargeModel->find_component($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.charge_types.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->ChargeModel->find_component($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->require_charge_type_access((int)$row['charge_type_id']);
        $this->ChargeModel->delete_component($id);
        api_ok(null, ['message' => 'Component dihapus']);
    }

    public function reorder(): void
    {
        $this->require_any_permission(['app.services.finance.charge_types.manage']);

        $in = $this->json_input();
        $charge_type_id = (int)($in['charge_type_id'] ?? 0);
        $ordered_ids = $in['ordered_ids'] ?? [];

        if ($charge_type_id <= 0) {
            api_validation_error(['charge_type_id' => 'Wajib diisi']);
            return;
        }
        if (!is_array($ordered_ids) || count($ordered_ids) === 0) {
            api_validation_error(['ordered_ids' => 'Wajib diisi']);
            return;
        }

        $this->require_charge_type_access($charge_type_id);

        $ids = array_values(array_filter(array_map('intval', $ordered_ids), fn ($x) => $x > 0));
        if (count($ids) === 0) {
            api_validation_error(['ordered_ids' => 'Tidak valid']);
            return;
        }

        $ok = $this->ChargeModel->reorder_components($charge_type_id, $ids);
        if (!$ok) {
            api_validation_error(['ordered_ids' => 'Ada komponen yang tidak valid / tidak sesuai jenis iuran']);
            return;
        }

        api_ok(null, ['message' => 'Urutan komponen diperbarui']);
    }
}
