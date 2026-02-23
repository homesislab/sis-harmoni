<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoices extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Invoice_model', 'InvoiceModel');
        $this->load->model('Payment_model', 'PaymentModel');
        $this->load->model('Charge_model', 'ChargeModel');
    }

    /**
     * Attach payment intent aggregation to invoice rows:
     * - pending_payment_count
     * - last_payment_status
     * - last_payment_note
     * - last_payment_paid_at
     *
     * Source: payment_invoice_intents + payments (same as /my/invoices/show)
     */
    private function attach_payment_agg_to_invoices(array $items): array
    {
        if (empty($items)) {
            return $items;
        }

        $ids = array_values(array_filter(array_map(function ($x) {
            return (int)($x['id'] ?? 0);
        }, $items)));

        if (empty($ids)) {
            return $items;
        }

        $rows = $this->db->select("
                pii.invoice_id,
                SUM(CASE WHEN p.status='pending' THEN 1 ELSE 0 END) as pending_payment_count,
                SUBSTRING_INDEX(GROUP_CONCAT(p.status ORDER BY p.id DESC), ',', 1) as last_payment_status,
                SUBSTRING_INDEX(GROUP_CONCAT(p.note ORDER BY p.id DESC SEPARATOR '||'), '||', 1) as last_payment_note,
                SUBSTRING_INDEX(GROUP_CONCAT(p.paid_at ORDER BY p.id DESC), ',', 1) as last_payment_paid_at
            ", false)
            ->from('payment_invoice_intents pii')
            ->join('payments p', 'p.id=pii.payment_id', 'left')
            ->where_in('pii.invoice_id', $ids)
            ->group_by('pii.invoice_id')
            ->get()->result_array();

        $map = [];
        foreach ($rows as $r) {
            $iid = (int)($r['invoice_id'] ?? 0);
            $map[$iid] = [
                'pending_payment_count' => (int)($r['pending_payment_count'] ?? 0),
                'last_payment_status' => $r['last_payment_status'] ?? null,
                'last_payment_note' => $r['last_payment_note'] ?? null,
                'last_payment_paid_at' => $r['last_payment_paid_at'] ?? null,
            ];
        }

        foreach ($items as &$it) {
            $iid = (int)($it['id'] ?? 0);
            $agg = $map[$iid] ?? [
                'pending_payment_count' => 0,
                'last_payment_status' => null,
                'last_payment_note' => null,
                'last_payment_paid_at' => null,
            ];
            $it = array_merge($it, $agg);
        }
        unset($it);

        return $items;
    }

    /**
     * Attach agg to a single invoice row (associative array)
     */
    private function attach_payment_agg_to_invoice(array $inv, int $invoiceId): array
    {
        $agg = $this->db->select("
                SUM(CASE WHEN p.status='pending' THEN 1 ELSE 0 END) as pending_payment_count,
                SUBSTRING_INDEX(GROUP_CONCAT(p.status ORDER BY p.id DESC), ',', 1) as last_payment_status,
                SUBSTRING_INDEX(GROUP_CONCAT(p.note ORDER BY p.id DESC SEPARATOR '||'), '||', 1) as last_payment_note,
                SUBSTRING_INDEX(GROUP_CONCAT(p.paid_at ORDER BY p.id DESC), ',', 1) as last_payment_paid_at
            ", false)
            ->from('payment_invoice_intents pii')
            ->join('payments p', 'p.id=pii.payment_id', 'left')
            ->where('pii.invoice_id', (int)$invoiceId)
            ->get()->row_array();

        $inv['pending_payment_count'] = (int)($agg['pending_payment_count'] ?? 0);
        $inv['last_payment_status'] = $agg['last_payment_status'] ?? null;
        $inv['last_payment_note'] = $agg['last_payment_note'] ?? null;
        $inv['last_payment_paid_at'] = $agg['last_payment_paid_at'] ?? null;

        return $inv;
    }

    public function index(): void
    {
        $this->require_any_permission(['app.services.finance.invoices.manage']);

        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $filters = [
            'household_id' => $this->input->get('household_id') ? (int)$this->input->get('household_id') : null,
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'period' => $this->input->get('period') ? (string)$this->input->get('period') : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'charge_type_id' => $this->input->get('charge_type_id') ? (int)$this->input->get('charge_type_id') : null,
        ];

        $res = $this->InvoiceModel->paginate($page, $per, $filters);

        $items = isset($res['items']) && is_array($res['items']) ? $res['items'] : [];
        $items = $this->attach_payment_agg_to_invoices($items);

        api_ok(['items' => $items], $res['meta'] ?? null);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.services.finance.invoices.manage']);

        $in = $this->json_input();

        $err = [];
        $household_id   = (int)($in['household_id'] ?? 0);
        $charge_type_id = (int)($in['charge_type_id'] ?? 0);
        $period         = trim((string)($in['period'] ?? ''));
        $total_amount   = isset($in['total_amount']) ? (float)$in['total_amount'] : -1;

        if ($household_id <= 0) {
            $err['household_id'] = 'Wajib diisi';
        }
        if ($charge_type_id <= 0) {
            $err['charge_type_id'] = 'Wajib diisi';
        }
        if ($period === '') {
            $err['period'] = 'Wajib diisi (YYYY-MM)';
        }
        if ($total_amount < 0) {
            $err['total_amount'] = 'Wajib diisi (>=0)';
        }

        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->InvoiceModel->create([
            'household_id'   => $household_id,
            'charge_type_id' => $charge_type_id,
            'period'         => $period,
            'total_amount'   => $total_amount,
            'status'         => $in['status'] ?? 'unpaid',
            'note'           => $in['note'] ?? null,
        ]);

        $row = $this->InvoiceModel->find_by_id($id);
        if ($row) {
            $row = $this->attach_payment_agg_to_invoice($row, (int)$id);
        }

        api_ok($row, null, 201);
    }

    public function show(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.invoices.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $inv = $this->InvoiceModel->find_by_id($id);
        if (!$inv) {
            api_not_found();
            return;
        }

        $inv = $this->attach_payment_agg_to_invoice($inv, (int)$id);

        $lines  = $this->InvoiceModel->list_lines($id);

        $allocs = $this->PaymentModel->list_invoice_allocations_for_invoice($id);

        $comp   = $this->PaymentModel->list_component_allocations_for_invoice($id);

        api_ok([
            'invoice' => $inv,
            'lines' => $lines,
            'invoice_allocations' => $allocs,
            'component_allocations' => $comp
        ]);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.invoices.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->InvoiceModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $payload = [];

        if (array_key_exists('note', $in)) {
            $payload['note'] = $in['note'];
        }
        if (array_key_exists('status', $in)) {
            $payload['status'] = $in['status'];
        }
        if (array_key_exists('total_amount', $in)) {
            $payload['total_amount'] = (float)$in['total_amount'];
        }

        if (!$payload) {
            api_validation_error(['payload' => 'Tidak ada field yang diupdate']);
            return;
        }

        $this->InvoiceModel->update($id, $payload);

        $updated = $this->InvoiceModel->find_by_id($id);
        if ($updated) {
            $updated = $this->attach_payment_agg_to_invoice($updated, (int)$id);
        }

        api_ok($updated);
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.invoices.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->InvoiceModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->InvoiceModel->delete($id);
        api_ok(null, ['message' => 'Invoice dihapus']);
    }
}
