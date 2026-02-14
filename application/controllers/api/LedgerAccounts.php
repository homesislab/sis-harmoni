<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LedgerAccounts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_any_permission(['app.services.finance.ledger_accounts.manage']);
        $this->load->model('Ledger_model', 'LedgerModel');
    }

    public function index(): void
    {
        $type = $this->input->get('type') ? (string)$this->input->get('type') : null;

        if ($type !== null && !in_array($type, ['paguyuban', 'dkm'], true)) {
            api_validation_error(['type' => 'Harus paguyuban|dkm']);
            return;
        }

        $items = $this->LedgerModel->list_accounts($type);
        api_ok(['items' => $items]);
    }

    public function store(): void
    {
        $in = $this->json_input();

        $name = trim((string)($in['name'] ?? ''));
        $type = $this->input->get('type') ? (string)$this->input->get('type') : (string)($in['type'] ?? '');

        $fields = [];
        if ($name === '') {
            $fields['name'] = 'Wajib diisi';
        }
        if (!in_array($type, ['paguyuban', 'dkm'], true)) {
            $fields['type'] = 'Harus paguyuban|dkm';
        }
        if (!empty($fields)) {
            api_validation_error($fields);
            return;
        }

        $id = $this->LedgerModel->create_account([
            'name' => $name,
            'type' => $type,
            'created_by' => (int)($this->auth_user['id'] ?? 0),
        ]);

        api_ok(['id' => (int)$id], null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $acc = $this->LedgerModel->find_account($id);
        if (!$acc) {
            api_not_found();
            return;
        }

        $stats = $this->LedgerModel->account_stats($id);
        api_ok(['account' => $acc, 'stats' => $stats]);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $acc = $this->LedgerModel->find_account($id);
        if (!$acc) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $name = trim((string)($in['name'] ?? ''));

        if ($name === '') {
            api_validation_error(['name' => 'Wajib diisi']);
            return;
        }

        $this->LedgerModel->update_account($id, [
            'name' => $name,
            'updated_by' => (int)($this->auth_user['id'] ?? 0),
        ]);

        api_ok(['ok' => true]);
    }

    public function destroy(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $acc = $this->LedgerModel->find_account($id);
        if (!$acc) {
            api_not_found();
            return;
        }

        $bal = (float)($acc['balance'] ?? 0);
        if ($bal != 0.0) {
            api_error('CONFLICT', 'Akun tidak bisa dihapus karena saldo belum 0', null, 409);
            return;
        }

        if ($this->LedgerModel->has_entries($id)) {
            api_error('CONFLICT', 'Akun tidak bisa dihapus karena sudah ada mutasi', null, 409);
            return;
        }

        $this->LedgerModel->soft_delete_account($id, (int)($this->auth_user['id'] ?? 0));
        api_ok(['ok' => true]);
    }
}
