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
        $transfer_to_ledger_account_id = (int)($in['transfer_to_ledger_account_id'] ?? 0);
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

        if ($transfer_to_ledger_account_id > 0) {
            if ($ledger_account_id <= 0) {
                $fields['ledger_account_id'] = 'Akun asal wajib diisi';
            }
            if ($transfer_to_ledger_account_id <= 0) {
                $fields['transfer_to_ledger_account_id'] = 'Akun tujuan wajib diisi';
            }
            if ($ledger_account_id > 0 && $ledger_account_id === $transfer_to_ledger_account_id) {
                $fields['transfer_to_ledger_account_id'] = 'Akun tujuan harus berbeda';
            }
        }

        if (!empty($fields)) {
            api_validation_error($fields);
            return;
        }

        if ($transfer_to_ledger_account_id > 0) {
            $from = $this->LedgerModel->find_account($ledger_account_id);
            $to = $this->LedgerModel->find_account($transfer_to_ledger_account_id);

            if (!$from) {
                api_validation_error(['ledger_account_id' => 'Akun asal tidak ditemukan']);
                return;
            }
            if (!$to) {
                api_validation_error(['transfer_to_ledger_account_id' => 'Akun tujuan tidak ditemukan']);
                return;
            }
            if (($from['type'] ?? null) !== ($to['type'] ?? null)) {
                api_validation_error(['transfer_to_ledger_account_id' => 'Mutasi antar kas harus dalam unit pengelola yang sama']);
                return;
            }

            $baseDescription = trim((string)($in['description'] ?? ($in['note'] ?? '')));
            $outDescription = $baseDescription !== ''
                ? $baseDescription . ' | ke ' . ($to['name'] ?? ('Akun #' . $transfer_to_ledger_account_id))
                : 'Mutasi ke ' . ($to['name'] ?? ('Akun #' . $transfer_to_ledger_account_id));
            $inDescription = $baseDescription !== ''
                ? $baseDescription . ' | dari ' . ($from['name'] ?? ('Akun #' . $ledger_account_id))
                : 'Mutasi dari ' . ($from['name'] ?? ('Akun #' . $ledger_account_id));

            $this->db->trans_start();

            $outId = $this->LedgerModel->create_entry([
                'ledger_account_id' => $ledger_account_id,
                'direction' => 'out',
                'amount' => $amount,
                'category' => 'Mutasi Antar Kas',
                'description' => $outDescription,
                'occurred_at' => $occurred_at,
                'source_type' => 'ledger_transfer',
                'source_id' => null,
                'created_by' => (int)($this->auth_user['id'] ?? 0),
            ]);

            $inId = $this->LedgerModel->create_entry([
                'ledger_account_id' => $transfer_to_ledger_account_id,
                'direction' => 'in',
                'amount' => $amount,
                'category' => 'Mutasi Antar Kas',
                'description' => $inDescription,
                'occurred_at' => $occurred_at,
                'source_type' => 'ledger_transfer',
                'source_id' => null,
                'created_by' => (int)($this->auth_user['id'] ?? 0),
            ]);

            $this->db->trans_complete();

            api_ok([
                'transfer' => true,
                'out_entry_id' => (int)$outId,
                'in_entry_id' => (int)$inId,
            ], null, 201);
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
