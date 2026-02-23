<?php

defined('BASEPATH') or exit('No direct script access allowed');

class LedgerEntries extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_any_permission(['app.services.finance.ledger_transactions.manage']);
        $this->load->model('Ledger_model', 'LedgerModel');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $filters = [];
        if ($this->input->get('ledger_account_id')) {
            $filters['ledger_account_id'] = (int)$this->input->get('ledger_account_id');
        }
        if ($this->input->get('direction')) {
            $filters['direction'] = (string)$this->input->get('direction');
        }
        if ($this->input->get('from')) {
            $filters['from'] = (string)$this->input->get('from');
        }
        if ($this->input->get('to')) {
            $filters['to'] = (string)$this->input->get('to');
        }

        $res = $this->LedgerModel->paginate_entries($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();

        $ledger_account_id = (int)($in['ledger_account_id'] ?? ($in['account_id'] ?? 0));
        $direction = (string)($in['direction'] ?? ($in['type'] ?? ''));
        $amount = (float)($in['amount'] ?? 0);
        $occurred_at = trim((string)($in['occurred_at'] ?? ($in['entry_date'] ?? '')));

        if ($direction === 'credit') {
            $direction = 'in';
        }
        if ($direction === 'debit') {
            $direction = 'out';
        }

        $fields = [];
        if ($ledger_account_id <= 0) {
            $fields['ledger_account_id'] = 'Wajib diisi';
        }
        if (!in_array($direction, ['in', 'out'], true)) {
            $fields['direction'] = 'Harus in|out';
        }
        if ($amount <= 0) {
            $fields['amount'] = 'Harus > 0';
        }
        if ($occurred_at === '') {
            $occurred_at = date('Y-m-d H:i:s');
        }

        if (!empty($fields)) {
            api_validation_error($fields);
            return;
        }

        $id = $this->LedgerModel->create_entry([
            'ledger_account_id' => $ledger_account_id,
            'direction' => $direction,
            'amount' => $amount,
            'category' => $in['category'] ?? null,
            'description' => $in['description'] ?? ($in['note'] ?? null),
            'occurred_at' => $occurred_at,
            'source_type' => $in['source_type'] ?? ($in['source_table'] ?? 'manual'),
            'source_id' => $in['source_id'] ?? null,
            'created_by' => (int)($this->auth_user['id'] ?? 0),
        ]);

        api_ok(['id' => (int)$id], null, 201);
    }
}
