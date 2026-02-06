<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Invoice_model','InvoiceModel');
        $this->load->model('Payment_model','PaymentModel');
        $this->load->model('Charge_model','ChargeModel');
    }

    public function index(): void
    {
        $this->require_any_permission(['billing.manage']);

        $page = max(1, (int)$this->input->get('page'));
        $per  = min(200, max(1, (int)$this->input->get('per_page') ?: 30));

        $filters = [
            'household_id' => $this->input->get('household_id') ? (int)$this->input->get('household_id') : null,
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'period' => $this->input->get('period') ? (string)$this->input->get('period') : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'charge_type_id' => $this->input->get('charge_type_id') ? (int)$this->input->get('charge_type_id') : null,
        ];

        $res = $this->InvoiceModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_any_permission(['billing.manage']);

        $in = $this->json_input();

        $err = [];
        $household_id   = (int)($in['household_id'] ?? 0);
        $charge_type_id = (int)($in['charge_type_id'] ?? 0);
        $period         = trim((string)($in['period'] ?? ''));
        $total_amount   = isset($in['total_amount']) ? (float)$in['total_amount'] : -1;

        if ($household_id <= 0) $err['household_id'] = 'Wajib diisi';
        if ($charge_type_id <= 0) $err['charge_type_id'] = 'Wajib diisi';
        if ($period === '') $err['period'] = 'Wajib diisi (YYYY-MM)';
        if ($total_amount < 0) $err['total_amount'] = 'Wajib diisi (>=0)';

        if ($err) { api_validation_error($err); return; }

        $id = $this->InvoiceModel->create([
            'household_id'   => $household_id,
            'charge_type_id' => $charge_type_id,
            'period'         => $period,
            'total_amount'   => $total_amount,
            'status'         => $in['status'] ?? 'unpaid',
            'note'           => $in['note'] ?? null,
        ]);

        api_ok($this->InvoiceModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        $this->require_any_permission(['billing.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $inv = $this->InvoiceModel->find_by_id($id);
        if (!$inv) { api_not_found(); return; }

        $lines = $this->InvoiceModel->list_lines($id);
        $allocs = $this->PaymentModel->list_invoice_allocations_for_invoice($id);
        $comp = $this->PaymentModel->list_component_allocations_for_invoice($id);

        api_ok(['invoice'=>$inv,'lines'=>$lines,'invoice_allocations'=>$allocs,'component_allocations'=>$comp]);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['billing.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->InvoiceModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $payload = [];

        if (array_key_exists('note', $in)) $payload['note'] = $in['note'];
        if (array_key_exists('status', $in)) $payload['status'] = $in['status'];
        if (array_key_exists('total_amount', $in)) $payload['total_amount'] = (float)$in['total_amount'];

        if (!$payload) {
            api_validation_error(['payload' => 'Tidak ada field yang diupdate']);
            return;
        }

        $this->InvoiceModel->update($id, $payload);
        api_ok($this->InvoiceModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['billing.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->InvoiceModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->InvoiceModel->delete($id);
        api_ok(null, ['message' => 'Invoice dihapus']);
    }
}
