<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Payment_model', 'PaymentModel');
        $this->load->model('Invoice_model', 'InvoiceModel');
        $this->load->model('Charge_model', 'ChargeModel');
        $this->load->model('Ledger_model', 'LedgerModel');
    }

    private function require_payment_org_access(int $payment_id): array
    {
        $intentInvoices = $this->PaymentModel->list_intent_invoices($payment_id);
        foreach ($intentInvoices as $item) {
            $this->require_org_access($item['charge_category'] ?? null);
        }
        return $intentInvoices;
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $filters = [
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'payer_household_id' => $this->input->get('payer_household_id') ? (int)$this->input->get('payer_household_id') : null,
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        if (!$this->has_permission('app.services.finance.payments.verify')) {
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) {
                api_error('FORBIDDEN', 'Akun belum terhubung ke household', 403);
                return;
            }
            $filters['payer_household_id'] = $hhid;
        } else {
            $this->require_any_permission(['app.services.finance.payments.verify']);
            $rawCategory = trim((string)($this->input->get('category') ?? ''));
            $filters['category'] = $this->constrain_org_filter($rawCategory !== '' ? $rawCategory : null);
        }

        $res = $this->PaymentModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $hhid = (int)($this->auth_household_id ?? 0);
        if ($hhid <= 0) {
            api_error('FORBIDDEN', 'Akun belum terhubung ke household', 403);
            return;
        }

        $amount = (float)($in['amount'] ?? 0);
        if ($amount <= 0) {
            api_validation_error(['amount' => 'amount harus > 0']);
            return;
        }

        $invoice_ids = $in['invoice_ids'] ?? [];
        if (!is_array($invoice_ids) || empty($invoice_ids)) {
            api_validation_error(['invoice_ids' => 'wajib diisi']);
            return;
        }

        $invoice_ids = array_values(array_unique(array_map(function ($x) {
            return (int)$x;
        }, $invoice_ids)));
        $invoice_ids = array_values(array_filter($invoice_ids, fn ($v) => $v > 0));
        if (empty($invoice_ids)) {
            api_validation_error(['invoice_ids' => 'wajib diisi']);
            return;
        }

        $valid = $this->InvoiceModel->list_by_household_and_ids($hhid, $invoice_ids);
        if (count($valid) !== count($invoice_ids)) {
            api_validation_error(['invoice_ids' => 'Ada invoice tidak valid / bukan milik household']);
            return;
        }
        foreach ($valid as $invoice) {
            $this->require_org_access($invoice['charge_category'] ?? null);
        }

        $paid_at = $in['paid_at'] ?? date('Y-m-d H:i:s');

        $this->db->trans_start();

        $id = $this->PaymentModel->create([
            'payer_household_id' => $hhid,
            'amount' => $amount,
            'paid_at' => $paid_at,
            'proof_file_url' => $in['proof_file_url'] ?? null,
            'note' => $in['note'] ?? null,
            'status' => 'pending',
        ]);

        $this->PaymentModel->insert_intents($id, $invoice_ids);

        $this->db->trans_complete();

        $amt = number_format((float)$amount, 0, ',', '.');
        $labels = array_map(function ($x) {
            $name = $x['charge_name'] ?? 'Tagihan';
            $period = $x['period'] ?? '';
            return trim($name . ' ' . $period);
        }, $valid);
        $labels = array_values(array_filter($labels, fn ($v) => $v !== ''));
        $shown = array_slice($labels, 0, 2);
        $more = max(0, count($labels) - count($shown));
        $labelText = $shown ? implode(', ', $shown) : 'tagihan terpilih';
        if ($more > 0) {
            $labelText .= ' (+' . $more . ' lagi)';
        }
        audit_log($this, 'Mengirim konfirmasi pembayaran', 'Mengirim konfirmasi pembayaran Rp ' . $amt . ' untuk ' . $labelText);
        api_ok($this->PaymentModel->find_by_id($id), null, 201);
    }

    public function manual_store(): void
    {
        $this->require_any_permission(['app.services.finance.payments.verify']);

        $in = $this->json_input();
        $household_id = (int)($in['household_id'] ?? 0);
        $charge_type_id = (int)($in['charge_type_id'] ?? 0);
        $invoice_ids = $in['invoice_ids'] ?? [];
        $periods = $in['periods'] ?? [];
        $paid_at = trim((string)($in['paid_at'] ?? date('Y-m-d H:i:s')));
        $note = trim((string)($in['note'] ?? ''));
        $amountInput = array_key_exists('amount', $in) ? (float)$in['amount'] : null;

        $errors = [];
        if ($household_id <= 0) {
            $errors['household_id'] = 'Wajib diisi';
        }

        $invoice_ids = is_array($invoice_ids) ? array_values(array_unique(array_map(fn ($x) => (int)$x, $invoice_ids))) : [];
        $invoice_ids = array_values(array_filter($invoice_ids, fn ($x) => $x > 0));

        $cleanPeriods = [];
        if (is_array($periods)) {
            foreach ($periods as $period) {
                $period = trim((string)$period);
                if ($period === '') {
                    continue;
                }
                if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
                    $errors['periods'] = 'Format periode harus YYYY-MM';
                    break;
                }
                $cleanPeriods[] = $period;
            }
        }
        $cleanPeriods = array_values(array_unique($cleanPeriods));

        if (!empty($cleanPeriods) && $charge_type_id <= 0) {
            $errors['charge_type_id'] = 'Jenis iuran wajib diisi untuk periode baru';
        }

        if ($charge_type_id > 0) {
            $chargeType = $this->ChargeModel->find_type($charge_type_id);
            if (!$chargeType) {
                $errors['charge_type_id'] = 'Jenis iuran tidak ditemukan';
            } else {
                $this->require_org_access($chargeType['category'] ?? null);
            }
        }

        if (empty($invoice_ids) && empty($cleanPeriods)) {
            $errors['invoice_ids'] = 'Pilih minimal 1 invoice atau 1 periode baru';
        }

        if (empty($invoice_ids) && empty($cleanPeriods)) {
            $errors['periods'] = 'Pilih minimal 1 invoice atau 1 periode baru';
        }

        if (empty($invoice_ids)) {
            if ($charge_type_id <= 0) {
                $errors['charge_type_id'] = 'Wajib diisi';
            }
            if (empty($cleanPeriods)) {
                $errors['periods'] = 'Pilih minimal 1 periode';
            }
        }

        if (!empty($errors)) {
            api_validation_error($errors);
            return;
        }

        $ensured = ['created_invoice_ids' => []];
        $this->db->trans_start();

        if (!empty($cleanPeriods)) {
            $ensured = $this->_ensure_manual_invoices($household_id, $charge_type_id, $cleanPeriods);
            if (!$ensured['ok']) {
                $this->db->trans_rollback();
                api_validation_error($ensured['errors']);
                return;
            }
            $invoice_ids = array_values(array_unique(array_merge($invoice_ids, $ensured['invoice_ids'])));
        }

        $invoiceRows = $this->InvoiceModel->list_by_household_and_ids($household_id, $invoice_ids);
        if (count($invoiceRows) !== count($invoice_ids)) {
            $this->db->trans_rollback();
            api_validation_error(['invoice_ids' => 'Ada invoice yang tidak valid untuk KK ini']);
            return;
        }

        foreach ($invoiceRows as $invoiceRow) {
            $this->require_org_access($invoiceRow['charge_category'] ?? null);
        }

        usort($invoiceRows, function ($a, $b) {
            $pa = (string)($a['period'] ?? '');
            $pb = (string)($b['period'] ?? '');
            if ($pa === $pb) {
                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            }
            return strcmp($pa, $pb);
        });

        $invoice_ids = array_values(array_map(fn ($row) => (int)$row['id'], $invoiceRows));

        $totalOutstanding = 0.0;
        foreach ($invoiceRows as $row) {
            $invId = (int)($row['id'] ?? 0);
            $invTotal = (float)($row['total_amount'] ?? 0);
            $alreadyPaid = $this->PaymentModel->sum_allocated_for_invoice($invId);
            $remain = max(0.0, $invTotal - $alreadyPaid);
            $totalOutstanding += $remain;
        }
        $totalOutstanding = round($totalOutstanding, 2);

        if ($totalOutstanding <= 0.0001) {
            $this->db->trans_rollback();
            api_validation_error(['amount' => 'Seluruh tagihan terpilih sudah lunas']);
            return;
        }

        $amount = $amountInput;
        if ($amount === null || $amount <= 0) {
            $amount = $totalOutstanding;
        }
        $amount = round((float)$amount, 2);

        if ($amount <= 0) {
            $this->db->trans_rollback();
            api_validation_error(['amount' => 'Nominal harus lebih dari 0']);
            return;
        }
        if ($amount - $totalOutstanding > 0.01) {
            $this->db->trans_rollback();
            api_validation_error(['amount' => 'Nominal lebih besar dari total sisa tagihan yang dipilih']);
            return;
        }

        $manualNote = 'Pembayaran manual bendahara';
        if ($note !== '') {
            $manualNote .= ' - ' . $note;
        }

        $paymentId = $this->PaymentModel->create([
            'payer_household_id' => $household_id,
            'amount' => $amount,
            'paid_at' => $paid_at !== '' ? $paid_at : date('Y-m-d H:i:s'),
            'proof_file_url' => null,
            'note' => $manualNote,
            'status' => 'pending',
        ]);

        $this->PaymentModel->insert_intents($paymentId, $invoice_ids);

        $pay = $this->PaymentModel->find_by_id($paymentId);
        $ledgerDescription = $this->_build_payment_ledger_description($household_id, $paymentId, true);
        $auto = $this->_build_auto_allocations($pay ?: []);
        if (!$auto['ok']) {
            $this->db->trans_rollback();
            api_validation_error(['payment' => $auto['message']]);
            return;
        }

        $this->PaymentModel->approve(
            $paymentId,
            (int)($this->auth_user['id'] ?? 0),
            $auto['invoice_allocations'],
            $auto['component_allocations']
        );

        foreach ($auto['invoice_allocations'] as $a) {
            $inv_id = (int)($a['invoice_id'] ?? 0);
            if ($inv_id <= 0) {
                continue;
            }
            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) {
                continue;
            }

            $paid_total = $this->PaymentModel->sum_allocated_for_invoice($inv_id);
            $total = (float)($inv['total_amount'] ?? 0);

            if ($paid_total <= 0.0001) {
                $this->InvoiceModel->update_status($inv_id, 'unpaid');
            } elseif ($paid_total + 0.01 < $total) {
                $this->InvoiceModel->update_status($inv_id, 'partial');
            } else {
                $this->InvoiceModel->update_status($inv_id, 'paid');
            }
        }

        foreach ($auto['component_allocations'] as $a) {
            $cc_id = (int)($a['charge_component_id'] ?? 0);
            $amt = (float)($a['allocated_amount'] ?? 0);
            if ($cc_id <= 0 || $amt <= 0) {
                continue;
            }

            $cc = $this->ChargeModel->find_component($cc_id);
            $la_id = (int)($cc['ledger_account_id'] ?? 0);
            if ($la_id <= 0) {
                continue;
            }

            $this->LedgerModel->create_entry([
                'ledger_account_id' => $la_id,
                'direction' => 'in',
                'amount' => $amt,
                'category' => 'Pembayaran Iuran',
                'description' => $ledgerDescription,
                'occurred_at' => $paid_at !== '' ? $paid_at : date('Y-m-d H:i:s'),
                'source_type' => 'payment',
                'source_id' => $paymentId,
                'created_by' => (int)($this->auth_user['id'] ?? 0),
            ]);
        }

        $this->db->trans_complete();

        audit_log(
            $this,
            'Mencatat pembayaran manual',
            'Mencatat pembayaran manual Rp ' . number_format($amount, 0, ',', '.') . ' untuk invoice: ' . implode(', ', $invoice_ids)
        );

        api_ok([
            'payment' => $this->PaymentModel->find_by_id($paymentId),
            'invoice_ids' => $invoice_ids,
            'created_invoice_ids' => $ensured['created_invoice_ids'] ?? [],
        ], null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $pay = $this->PaymentModel->find_by_id($id);
        if (!$pay) {
            api_not_found();
            return;
        }

        if (!$this->has_permission('app.services.finance.payments.verify')) {
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) {
                api_error('FORBIDDEN', 'Akun belum terhubung ke household', 403);
                return;
            }
            if ((int)($pay['payer_household_id'] ?? 0) !== $hhid) {
                api_error('FORBIDDEN', 'Tidak punya akses', 403);
                return;
            }
            $intents = $this->PaymentModel->list_intents($id);
            $intentInvoices = [];
            if (!empty($intents)) {
                $invIds = array_map(function ($x) {
                    return (int)($x['invoice_id'] ?? 0);
                }, $intents);
                $invIds = array_values(array_filter($invIds, fn ($v) => $v > 0));
                if ($invIds) {
                    $intentInvoices = $this->PaymentModel->list_intent_invoices($id);
                }
            }
        } else {
            $this->require_any_permission(['app.services.finance.payments.verify']);
            $intents = $this->PaymentModel->list_intents($id);
            $intentInvoices = $this->require_payment_org_access($id);
        }

        $invoice_allocs = $this->PaymentModel->list_invoice_allocations($id);
        $component_allocs = $this->PaymentModel->list_component_allocations($id);

        api_ok([
            'payment' => $pay,
            'intents' => $intents,
            'intent_invoices' => $intentInvoices,
            'invoice_allocations' => $invoice_allocs,
            'component_allocations' => $component_allocs,
        ]);
    }

    public function approve(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.payments.verify']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $pay = $this->PaymentModel->find_by_id($id);
        if (!$pay) {
            api_not_found();
            return;
        }
        if (($pay['status'] ?? '') !== 'pending') {
            api_error('CONFLICT', 'Payment status bukan pending', 409);
            return;
        }

        $this->require_payment_org_access($id);

        $in = $this->json_input();
        $invoice_allocs = $in['invoice_allocations'] ?? [];
        $component_allocs = $in['component_allocations'] ?? [];

        if (empty($invoice_allocs) || empty($component_allocs)) {
            $auto = $this->_build_auto_allocations($pay);
            if (!$auto['ok']) {
                api_validation_error(['approve' => $auto['message']]);
                return;
            }
            $invoice_allocs = $auto['invoice_allocations'];
            $component_allocs = $auto['component_allocations'];
        }

        if (!is_array($invoice_allocs) || empty($invoice_allocs)) {
            api_validation_error(['invoice_allocations' => 'wajib diisi']);
            return;
        }
        if (!is_array($component_allocs) || empty($component_allocs)) {
            api_validation_error(['component_allocations' => 'wajib diisi']);
            return;
        }

        $sum_inv = 0.0;
        foreach ($invoice_allocs as $a) {
            $sum_inv += (float)($a['allocated_amount'] ?? 0);
        }

        if (abs($sum_inv - (float)$pay['amount']) > 0.01) {
            api_validation_error(['invoice_allocations' => 'total allocated harus sama dengan amount payment']);
            return;
        }

        $sum_comp = 0.0;
        foreach ($component_allocs as $a) {
            $sum_comp += (float)($a['allocated_amount'] ?? 0);
        }

        if (abs($sum_comp - (float)$pay['amount']) > 0.01) {
            api_validation_error(['component_allocations' => 'total alokasi komponen harus sama dengan amount payment']);
            return;
        }

        $this->db->trans_start();

        $this->PaymentModel->approve($id, (int)$this->auth_user['id'], $invoice_allocs, $component_allocs);

        foreach ($invoice_allocs as $a) {
            $inv_id = (int)($a['invoice_id'] ?? 0);
            if ($inv_id <= 0) {
                continue;
            }
            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) {
                continue;
            }

            $paid_total = $this->PaymentModel->sum_allocated_for_invoice($inv_id);
            $total = (float)($inv['total_amount'] ?? 0);

            if ($paid_total <= 0.0001) {
                $this->InvoiceModel->update_status($inv_id, 'unpaid');
            } elseif ($paid_total + 0.01 < $total) {
                $this->InvoiceModel->update_status($inv_id, 'partial');
            } else {
                $this->InvoiceModel->update_status($inv_id, 'paid');
            }
        }

        foreach ($component_allocs as $a) {
            $cc_id = (int)($a['charge_component_id'] ?? 0);
            $amt   = (float)($a['allocated_amount'] ?? 0);
            if ($cc_id <= 0 || $amt <= 0) {
                continue;
            }

            $cc = $this->ChargeModel->find_component($cc_id);
            $la_id = (int)($cc['ledger_account_id'] ?? 0);
            if ($la_id <= 0) {
                continue;
            }

            $this->LedgerModel->create_entry([
                'ledger_account_id' => $la_id,
                'direction' => 'in',
                'amount' => $amt,
                'category' => 'Pembayaran Iuran',
                'description' => $this->_build_payment_ledger_description((int)($pay['payer_household_id'] ?? 0), $id, false),
                'occurred_at' => date('Y-m-d H:i:s'),
                'source_type' => 'payment',
                'source_id' => $id,
                'created_by' => (int)$this->auth_user['id'],
            ]);
        }

        $this->db->trans_complete();

        $amt = number_format((float)($pay['amount'] ?? 0), 0, ',', '.');
        $intentInvoices = $this->PaymentModel->list_intent_invoices($id);
        $labels = array_map(function ($x) {
            $name = $x['charge_name'] ?? 'Tagihan';
            $period = $x['period'] ?? '';
            return trim($name . ' ' . $period);
        }, $intentInvoices ?: []);
        $labels = array_values(array_filter($labels, fn ($v) => $v !== ''));
        $shown = array_slice($labels, 0, 2);
        $more = max(0, count($labels) - count($shown));
        $labelText = $shown ? implode(', ', $shown) : 'tagihan';
        if ($more > 0) {
            $labelText .= ' (+' . $more . ' lagi)';
        }
        audit_log($this, 'Menyetujui pembayaran', 'Menyetujui pembayaran Rp ' . $amt . ' untuk ' . $labelText);
        api_ok($this->PaymentModel->find_by_id($id));
    }

    public function reject(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.payments.verify']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $pay = $this->PaymentModel->find_by_id($id);
        if (!$pay) {
            api_not_found();
            return;
        }
        if (($pay['status'] ?? '') !== 'pending') {
            api_error('CONFLICT', 'Payment status bukan pending', 409);
            return;
        }

        $this->require_payment_org_access($id);

        $in = $this->json_input();
        $note = $in['note'] ?? ($in['reason'] ?? null);

        $this->PaymentModel->reject($id, (int)$this->auth_user['id'], $note);
        $amt = number_format((float)($pay['amount'] ?? 0), 0, ',', '.');
        $intentInvoices = $this->PaymentModel->list_intent_invoices($id);
        $labels = array_map(function ($x) {
            $name = $x['charge_name'] ?? 'Tagihan';
            $period = $x['period'] ?? '';
            return trim($name . ' ' . $period);
        }, $intentInvoices ?: []);
        $labels = array_values(array_filter($labels, fn ($v) => $v !== ''));
        $shown = array_slice($labels, 0, 2);
        $more = max(0, count($labels) - count($shown));
        $labelText = $shown ? implode(', ', $shown) : 'tagihan';
        if ($more > 0) {
            $labelText .= ' (+' . $more . ' lagi)';
        }
        audit_log($this, 'Menolak pembayaran', 'Menolak pembayaran Rp ' . $amt . ' untuk ' . $labelText);
        api_ok($this->PaymentModel->find_by_id($id));
    }

    private function _build_auto_allocations(array $pay): array
    {
        $payment_id = (int)($pay['id'] ?? 0);
        $payer_hh   = (int)($pay['payer_household_id'] ?? 0);
        $amount     = (float)($pay['amount'] ?? 0);

        if ($payment_id <= 0 || $amount <= 0) {
            return ['ok' => false, 'message' => 'Payment tidak valid'];
        }

        $intents = $this->PaymentModel->list_intents($payment_id);
        if (empty($intents)) {
            return ['ok' => false, 'message' => 'Tidak ada invoice intents pada pembayaran ini'];
        }

        $remainingPay = $amount;
        $invoice_allocs = [];

        foreach ($intents as $it) {
            $inv_id = (int)($it['invoice_id'] ?? 0);
            if ($inv_id <= 0) {
                continue;
            }

            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) {
                continue;
            }

            if ((int)($inv['household_id'] ?? 0) !== $payer_hh) {
                return ['ok' => false, 'message' => 'Ada invoice intents yang bukan milik household pembayar'];
            }

            $invTotal = (float)($inv['total_amount'] ?? 0);
            if ($invTotal <= 0.0001) {
                continue;
            }

            $alreadyPaid = $this->PaymentModel->sum_allocated_for_invoice($inv_id);
            $remainInv = max(0.0, $invTotal - $alreadyPaid);

            if ($remainInv <= 0.0001) {
                continue; // already fully covered
            }

            $alloc = min($remainInv, $remainingPay);
            if ($alloc <= 0.0001) {
                break;
            }

            $invoice_allocs[] = [
                'invoice_id' => $inv_id,
                'allocated_amount' => round($alloc, 2),
            ];

            $remainingPay -= $alloc;
            if ($remainingPay <= 0.0001) {
                break;
            }
        }

        if ($remainingPay > 0.01) {
            return ['ok' => false, 'message' => 'Nominal pembayaran lebih besar dari total sisa tagihan yang dipilih'];
        }

        $compMap = []; // cc_id => amount

        foreach ($invoice_allocs as $ia) {
            $inv_id = (int)$ia['invoice_id'];
            $allocAmt = (float)$ia['allocated_amount'];

            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) {
                continue;
            }

            $invTotal = (float)($inv['total_amount'] ?? 0);
            $chargeTypeId = (int)($inv['charge_type_id'] ?? 0);
            if ($invTotal <= 0.0001 || $chargeTypeId <= 0) {
                return ['ok' => false, 'message' => 'Invoice tidak punya charge_type_id / total_amount invalid'];
            }

            $components = $this->ChargeModel->list_components_by_charge_type($chargeTypeId);

            if (empty($components)) {
                return ['ok' => false, 'message' => 'Komponen iuran belum diset untuk charge type invoice'];
            }

            $sumComp = 0.0;
            foreach ($components as $c) {
                $sumComp += (float)($c['amount'] ?? 0);
            }
            if ($sumComp <= 0.0001) {
                return ['ok' => false, 'message' => 'Total komponen iuran 0'];
            }

            $running = 0.0;
            $n = count($components);

            for ($i = 0; $i < $n; $i++) {
                $cc_id = (int)($components[$i]['id'] ?? 0);
                $cAmt  = (float)($components[$i]['amount'] ?? 0);
                if ($cc_id <= 0) {
                    continue;
                }

                $portion = ($i === $n - 1)
                    ? round($allocAmt - $running, 2)
                    : round($allocAmt * ($cAmt / $sumComp), 2);

                $running += $portion;

                if (!isset($compMap[$cc_id])) {
                    $compMap[$cc_id] = 0.0;
                }
                $compMap[$cc_id] += $portion;
            }
        }

        $component_allocs = [];
        foreach ($compMap as $cc_id => $amt) {
            $amt = round((float)$amt, 2);
            if ($amt <= 0.0001) {
                continue;
            }
            $component_allocs[] = [
                'charge_component_id' => (int)$cc_id,
                'allocated_amount' => $amt,
            ];
        }

        $sumInv = 0.0;
        foreach ($invoice_allocs as $a) {
            $sumInv += (float)$a['allocated_amount'];
        }
        $sumComp = 0.0;
        foreach ($component_allocs as $a) {
            $sumComp += (float)$a['allocated_amount'];
        }

        if (abs($sumInv - (float)$amount) > 0.01) {
            return ['ok' => false, 'message' => 'Auto alokasi invoice tidak pas dengan amount payment'];
        }
        if (abs($sumComp - (float)$amount) > 0.01) {
            return ['ok' => false, 'message' => 'Auto alokasi komponen tidak pas dengan amount payment'];
        }

        return [
            'ok' => true,
            'invoice_allocations' => $invoice_allocs,
            'component_allocations' => $component_allocs,
        ];
    }

    private function _ensure_manual_invoices(int $household_id, int $charge_type_id, array $periods): array
    {
        if ($household_id <= 0) {
            return ['ok' => false, 'errors' => ['household_id' => 'KK tidak valid']];
        }
        if ($charge_type_id <= 0) {
            return ['ok' => false, 'errors' => ['charge_type_id' => 'Jenis iuran wajib diisi']];
        }
        if (empty($periods)) {
            return ['ok' => false, 'errors' => ['periods' => 'Pilih minimal 1 periode']];
        }

        $chargeType = $this->ChargeModel->find_type($charge_type_id);
        if (!$chargeType) {
            return ['ok' => false, 'errors' => ['charge_type_id' => 'Jenis iuran tidak ditemukan']];
        }

        $defaultAmount = (float)$this->ChargeModel->sum_components($charge_type_id);
        if ($defaultAmount <= 0.0001) {
            return ['ok' => false, 'errors' => ['charge_type_id' => 'Jenis iuran belum punya komponen nominal']];
        }

        $invoiceIds = [];
        $createdInvoiceIds = [];

        foreach ($periods as $period) {
            $exists = $this->InvoiceModel->find_by_household_charge_period($household_id, $charge_type_id, $period);
            if ($exists) {
                $invoiceIds[] = (int)$exists['id'];
                continue;
            }

            $invoiceId = $this->InvoiceModel->create([
                'household_id' => $household_id,
                'charge_type_id' => $charge_type_id,
                'period' => $period,
                'total_amount' => $defaultAmount,
                'status' => 'unpaid',
                'note' => 'Auto-create for manual payment',
            ]);

            $this->InvoiceModel->add_line($invoiceId, [
                'house_id' => null,
                'line_type' => 'base',
                'description' => ($chargeType['name'] ?? 'Iuran') . ' ' . $period,
                'qty' => 1,
                'unit_price' => $defaultAmount,
                'amount' => $defaultAmount,
                'sort_order' => 1,
            ]);

            $invoiceIds[] = (int)$invoiceId;
            $createdInvoiceIds[] = (int)$invoiceId;
        }

        return [
            'ok' => true,
            'invoice_ids' => array_values(array_unique($invoiceIds)),
            'created_invoice_ids' => $createdInvoiceIds,
        ];
    }

    private function _find_household_brief(int $household_id): ?array
    {
        if ($household_id <= 0) {
            return null;
        }

        $row = $this->db->select("
                hh.id,
                p.full_name as head_name,
                hs.block as house_block,
                hs.number as house_number,
                CONCAT(hs.block, '-', hs.number) as unit_code
            ", false)
            ->from('households hh')
            ->join('persons p', 'p.id = hh.head_person_id', 'left')
            ->join('house_occupancies ho', 'ho.household_id = hh.id AND ho.status = "active"', 'left')
            ->join('houses hs', 'hs.id = ho.house_id', 'left')
            ->where('hh.id', $household_id)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    private function _build_payment_ledger_description(int $household_id, int $payment_id, bool $isManual = false): string
    {
        $intentInvoices = $this->PaymentModel->list_intent_invoices($payment_id);
        $household = $this->_find_household_brief($household_id);

        $payerParts = [];
        $unitCode = trim((string)($household['unit_code'] ?? ''));
        $headName = trim((string)($household['head_name'] ?? ''));
        if ($unitCode !== '') {
            $payerParts[] = 'Unit ' . strtoupper($unitCode);
        }
        if ($headName !== '') {
            $payerParts[] = $headName;
        }
        $payerText = !empty($payerParts) ? implode(' / ', $payerParts) : 'warga';

        $labels = [];
        foreach ($intentInvoices as $x) {
            $charge = trim((string)($x['charge_name'] ?? 'Iuran'));
            $period = trim((string)($x['period'] ?? ''));
            $labels[] = trim($charge . ($period !== '' ? ' ' . $period : ''));
        }
        $labels = array_values(array_unique(array_filter($labels, fn ($v) => $v !== '')));

        $detailText = 'tagihan iuran';
        if (!empty($labels)) {
            $shown = array_slice($labels, 0, 2);
            $detailText = implode(', ', $shown);
            $more = count($labels) - count($shown);
            if ($more > 0) {
                $detailText .= ' +' . $more . ' tagihan';
            }
        }

        return ($isManual ? 'Pelunasan manual ' : 'Pembayaran iuran ')
            . $detailText
            . ' - '
            . $payerText;
    }
}
