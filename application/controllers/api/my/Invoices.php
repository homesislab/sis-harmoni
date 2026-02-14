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
        $hh_id = (int)($this->auth_household_id ?? 0);
        if ($hh_id <= 0) {
            api_error('FORBIDDEN','Akun belum terhubung ke KK/household',403);
            return;
        }

        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $filters = [
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'period' => $this->input->get('period') ? (string)$this->input->get('period') : null,
            'period_like' => $this->input->get('period_like') ? (string)$this->input->get('period_like') : null,
            'charge_type_id' => $this->input->get('charge_type_id') ? (int)$this->input->get('charge_type_id') : null,
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        $res = $this->InvoiceModel->paginate_for_household($hh_id, $page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function show(int $id = 0): void
    {
        $hh_id = (int)($this->auth_household_id ?? 0);
        if ($hh_id <= 0) {
            api_error('FORBIDDEN','Akun belum terhubung ke KK/household',403);
            return;
        }
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $inv = $this->InvoiceModel->find_by_household_and_id($hh_id, $id);
        if (!$inv) {
            api_not_found();
            return;
        }

        $agg = $this->db->select("
                SUM(CASE WHEN p.status='pending' THEN 1 ELSE 0 END) as pending_payment_count,
                SUBSTRING_INDEX(GROUP_CONCAT(p.status ORDER BY p.id DESC), ',', 1) as last_payment_status,
                SUBSTRING_INDEX(GROUP_CONCAT(p.note ORDER BY p.id DESC SEPARATOR '||'), '||', 1) as last_payment_note,
                SUBSTRING_INDEX(GROUP_CONCAT(p.paid_at ORDER BY p.id DESC), ',', 1) as last_payment_paid_at
            ", false)
            ->from('payment_invoice_intents pii')
            ->join('payments p', 'p.id=pii.payment_id', 'left')
            ->where('pii.invoice_id', (int)$id)
            ->get()->row_array();

        $inv['pending_payment_count'] = (int)($agg['pending_payment_count'] ?? 0);
        $inv['last_payment_status'] = $agg['last_payment_status'] ?? null;
        $inv['last_payment_note'] = $agg['last_payment_note'] ?? null;
        $inv['last_payment_paid_at'] = $agg['last_payment_paid_at'] ?? null;

        $lines = $this->InvoiceModel->list_lines($id);

        $allocs = $this->PaymentModel->list_invoice_allocations_for_invoice($id);

        api_ok([
            'invoice' => $inv,
            'lines' => $lines,
            'payment_allocations' => $allocs
        ]);
    }

    public function ensure(): void
    {
        $hh_id = (int)($this->auth_household_id ?? 0);
        if ($hh_id <= 0) {
            api_error('FORBIDDEN','Akun belum terhubung ke KK/household',403);
            return;
        }

        $in = $this->json_input();
        $periods = $in['periods'] ?? [];
        if (!is_array($periods) || empty($periods)) {
            api_validation_error(['periods' => 'Wajib diisi'], 'Validasi gagal');
            return;
        }

        $clean = [];
        foreach ($periods as $p) {
            $p = trim((string)$p);
            if ($p === '') {
                continue;
            }
            if (!preg_match('/^\d{4}-\d{2}$/', $p)) {
                api_validation_error(['periods' => 'Format periode harus YYYY-MM'], 'Validasi gagal');
                return;
            }
            $clean[] = $p;
        }
        $clean = array_values(array_unique($clean));
        if (empty($clean)) {
            api_validation_error(['periods' => 'Wajib diisi'], 'Validasi gagal');
            return;
        }

        $types = $this->db->from('charge_types')
            ->where('is_active', 1)
            ->where('is_periodic', 1)
            ->where('period_unit', 'monthly')
            ->order_by('id','ASC')
            ->get()->result_array();

        $created = 0;
        $this->db->trans_start();

        foreach ($clean as $period) {
            foreach ($types as $ct) {
                $ctId = (int)($ct['id'] ?? 0);
                if ($ctId <= 0) {
                    continue;
                }

                $exists = $this->InvoiceModel->find_by_household_charge_period($hh_id, $ctId, $period);
                if ($exists) {
                    continue;
                }

                $total = $this->ChargeModel->sum_components($ctId);
                $this->InvoiceModel->create([
                    'household_id' => $hh_id,
                    'charge_type_id' => $ctId,
                    'period' => $period,
                    'total_amount' => $total,
                    'status' => 'unpaid',
                    'note' => 'Auto-create for prepay selection',
                ]);
                $created++;
            }
        }

        $this->db->trans_complete();

        api_ok(['created' => $created]);
    }

    public function preview(): void
    {
        $hh_id = (int)($this->auth_household_id ?? 0);
        if ($hh_id <= 0) {
            api_error('FORBIDDEN','Akun belum terhubung ke KK/household',403);
            return;
        }

        $in = $this->json_input();
        $periods = $in['periods'] ?? [];
        if (!is_array($periods) || empty($periods)) {
            api_validation_error(['periods' => 'Wajib diisi'], 'Validasi gagal');
            return;
        }

        $clean = [];
        foreach ($periods as $p) {
            $p = trim((string)$p);
            if ($p === '') {
                continue;
            }
            if (!preg_match('/^\d{4}-\d{2}$/', $p)) {
                api_validation_error(['periods' => 'Format periode harus YYYY-MM'], 'Validasi gagal');
                return;
            }
            $clean[] = $p;
        }
        $clean = array_values(array_unique($clean));
        sort($clean);
        if (empty($clean)) {
            api_validation_error(['periods' => 'Wajib diisi'], 'Validasi gagal');
            return;
        }

        $types = $this->db->from('charge_types')
            ->where('is_active', 1)
            ->where('is_periodic', 1)
            ->where('period_unit', 'monthly')
            ->order_by('id', 'ASC')
            ->get()->result_array();

        $charge_items = [];
        $charge_total = 0.0;

        foreach ($types as $ct) {
            $ctId = (int)($ct['id'] ?? 0);
            if ($ctId <= 0) {
                continue;
            }

            $unit_total = (float)$this->ChargeModel->sum_components($ctId);
            $months = count($clean);
            $subtotal = $unit_total * $months;

            $charge_items[] = [
                'charge_type_id' => $ctId,
                'name' => $ct['name'] ?? null,
                'months' => $months,
                'unit_total' => $unit_total,
                'subtotal' => $subtotal,
            ];

            $charge_total += $subtotal;
        }

        api_ok([
            'periods' => $clean,
            'items' => $charge_items,
            'total' => $charge_total,
        ]);
    }
}
