<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Inventories extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Inventory_model', 'InventoryModel');
        $this->load->model('Inventory_log_model', 'InventoryLogModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)($this->input->get('per_page') ?: 20)));

        $can_manage = $this->has_permission('app.services.notes.inventories.manage');
        $filters = [
            'q' => $this->input->get('q') ? (string)$this->input->get('q') : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'condition' => $this->input->get('condition') ? (string)$this->input->get('condition') : null,
            'status' => $can_manage
                ? ($this->input->get('status') ? (string)$this->input->get('status') : null)
                : 'active',
        ];

        $res = $this->InventoryModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.notes.inventories.manage');
        $in = $this->json_input();
        if (!isset($in['location_text']) && isset($in['location'])) {
            $in['location_text'] = $in['location'];
        }
        $err = [];
        if (empty($in['code'])) {
            $err['code'] = 'Wajib diisi';
        }
        if (empty($in['name'])) {
            $err['name'] = 'Wajib diisi';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];

        $id = $this->InventoryModel->create($payload);
        $this->InventoryLogModel->create([
            'inventory_id' => $id,
            'action' => 'create',
            'note' => 'Created',
            'actor_user_id' => (int)$this->auth_user['id'],
        ]);

        api_ok($this->InventoryModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->InventoryModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        if (!$this->has_permission('app.services.notes.inventories.manage') && ($row['status'] ?? '') !== 'active') {
            api_not_found();
            return;
        }

        $logs = $this->InventoryLogModel->list_by_inventory($id, 50);
        api_ok(['inventory' => $row, 'logs' => $logs]);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.notes.inventories.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->InventoryModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        if (!isset($in['location_text']) && isset($in['location'])) {
            $in['location_text'] = $in['location'];
        }
        if (!isset($in['location_text']) && isset($in['location'])) {
            $in['location_text'] = $in['location'];
        }

        $from_loc = $row['location_text'] ?? null;
        $to_loc = array_key_exists('location_text', $in) ? ($in['location_text'] ?? null) : $from_loc;
        if ($from_loc !== $to_loc) {
            $this->InventoryLogModel->create([
                'inventory_id' => $id,
                'action' => 'move',
                'from_location' => $from_loc,
                'to_location' => $to_loc,
                'note' => $in['note'] ?? 'Move location',
                'actor_user_id' => (int)$this->auth_user['id'],
            ]);
        } else {
            $this->InventoryLogModel->create([
                'inventory_id' => $id,
                'action' => 'update',
                'note' => $in['note'] ?? 'Update',
                'actor_user_id' => (int)$this->auth_user['id'],
            ]);
        }

        $this->InventoryModel->update($id, $in);
        api_ok($this->InventoryModel->find_by_id($id));
    }

    public function archive(int $id = 0): void
    {
        $this->require_permission('app.services.notes.inventories.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->InventoryModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->InventoryModel->update($id, ['status' => 'archived']);
        $this->InventoryLogModel->create([
            'inventory_id' => $id,
            'action' => 'archive',
            'note' => 'Archived',
            'actor_user_id' => (int)$this->auth_user['id'],
        ]);
        api_ok($this->InventoryModel->find_by_id($id));
    }

    public function checkout(int $id = 0): void
    {
        $this->require_permission('app.services.notes.inventories.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->InventoryModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $this->InventoryLogModel->create([
            'inventory_id' => $id,
            'action' => 'checkout',
            'borrower_person_id' => isset($in['borrower_person_id']) ? (int)$in['borrower_person_id'] : null,
            'borrower_house_id' => isset($in['borrower_house_id']) ? (int)$in['borrower_house_id'] : null,
            'note' => $in['note'] ?? null,
            'actor_user_id' => (int)$this->auth_user['id'],
        ]);
        api_ok(['ok' => true]);
    }

    public function return_item(int $id = 0): void
    {
        $this->require_permission('app.services.notes.inventories.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->InventoryModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $this->InventoryLogModel->create([
            'inventory_id' => $id,
            'action' => 'return',
            'note' => $in['note'] ?? null,
            'actor_user_id' => (int)$this->auth_user['id'],
        ]);

        if (!empty($in['condition_after'])) {
            $this->InventoryModel->update($id, ['condition' => (string)$in['condition_after']]);
            $this->InventoryLogModel->create([
                'inventory_id' => $id,
                'action' => 'condition',
                'note' => 'Condition set after return',
                'actor_user_id' => (int)$this->auth_user['id'],
            ]);
        }

        api_ok(['ok' => true]);
    }
}
