<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Payment_model','PaymentModel');
        $this->load->model('Invoice_model','InvoiceModel');
        $this->load->model('Charge_model','ChargeModel');
        $this->load->model('Ledger_model','LedgerModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(200, max(1, (int)$this->input->get('per_page') ?: 30));

        $filters = [
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'payer_household_id' => $this->input->get('payer_household_id') ? (int)$this->input->get('payer_household_id') : null,
        ];

        if (!in_array('admin', $this->auth_roles, true)) {
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) { api_error('FORBIDDEN','Akun belum terhubung ke household',403); return; }
            $filters['payer_household_id'] = $hhid;
        } else {
            $this->require_any_permission(['finance.verify','billing.manage']);
        }

        $res = $this->PaymentModel->paginate($page,$per,$filters);
        api_ok(['items'=>$res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();
        $hhid = (int)($this->auth_household_id ?? 0);
        if ($hhid <= 0) { api_error('FORBIDDEN','Akun belum terhubung ke household',403); return; }

        $amount = (float)($in['amount'] ?? 0);
        if ($amount <= 0) { api_validation_error(['amount'=>'amount harus > 0']); return; }

        $invoice_ids = $in['invoice_ids'] ?? [];
        if (!is_array($invoice_ids) || empty($invoice_ids)) {
            api_validation_error(['invoice_ids'=>'wajib diisi']);
            return;
        }

        $invoice_ids = array_values(array_unique(array_map(function($x){ return (int)$x; }, $invoice_ids)));
        $invoice_ids = array_values(array_filter($invoice_ids, fn($v)=>$v>0));
        if (empty($invoice_ids)) {
            api_validation_error(['invoice_ids'=>'wajib diisi']);
            return;
        }

        $valid = $this->InvoiceModel->list_by_household_and_ids($hhid, $invoice_ids);
        if (count($valid) !== count($invoice_ids)) {
            api_validation_error(['invoice_ids'=>'Ada invoice tidak valid / bukan milik household']);
            return;
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

        audit_log($this,'payment_create','Create payment #'.$id);
        api_ok($this->PaymentModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $pay = $this->PaymentModel->find_by_id($id);
        if (!$pay) { api_not_found(); return; }

        if (!in_array('admin', $this->auth_roles, true)) {
            $hhid = (int)($this->auth_household_id ?? 0);
            if ($hhid <= 0) { api_error('FORBIDDEN','Akun belum terhubung ke household',403); return; }
            if ((int)($pay['payer_household_id'] ?? 0) !== $hhid) {
                api_error('FORBIDDEN','Tidak punya akses',403); return;
            }
        } else {
            $this->require_any_permission(['finance.verify','billing.manage']);
        }

        $intents = $this->PaymentModel->list_intents($id);
        $intentInvoices = [];
        if (!empty($intents)) {
            $invIds = array_map(function($x){ return (int)($x['invoice_id'] ?? 0); }, $intents);
            $invIds = array_values(array_filter($invIds, fn($v)=>$v>0));
            if ($invIds) $intentInvoices = $this->PaymentModel->list_intent_invoices($id);
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
        $this->require_any_permission(['finance.verify','billing.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $pay = $this->PaymentModel->find_by_id($id);
        if (!$pay) { api_not_found(); return; }
        if (($pay['status'] ?? '') !== 'pending') {
            api_error('CONFLICT','Payment status bukan pending',409);
            return;
        }

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
            api_validation_error(['invoice_allocations'=>'wajib diisi']);
            return;
        }
        if (!is_array($component_allocs) || empty($component_allocs)) {
            api_validation_error(['component_allocations'=>'wajib diisi']);
            return;
        }

        $sum_inv = 0.0;
        foreach ($invoice_allocs as $a) $sum_inv += (float)($a['allocated_amount'] ?? 0);

        if (abs($sum_inv - (float)$pay['amount']) > 0.01) {
            api_validation_error(['invoice_allocations'=>'total allocated harus sama dengan amount payment']);
            return;
        }

        $sum_comp = 0.0;
        foreach ($component_allocs as $a) $sum_comp += (float)($a['allocated_amount'] ?? 0);

        if (abs($sum_comp - (float)$pay['amount']) > 0.01) {
            api_validation_error(['component_allocations'=>'total alokasi komponen harus sama dengan amount payment']);
            return;
        }

        $this->db->trans_start();

        $this->PaymentModel->approve($id, (int)$this->auth_user['id'], $invoice_allocs, $component_allocs);

        foreach ($invoice_allocs as $a) {
            $inv_id = (int)($a['invoice_id'] ?? 0);
            if ($inv_id <= 0) continue;
            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) continue;

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
            if ($cc_id <= 0 || $amt <= 0) continue;

            $cc = $this->ChargeModel->find_component($cc_id);
            $la_id = (int)($cc['ledger_account_id'] ?? 0);
            if ($la_id <= 0) continue;

            $this->LedgerModel->create_entry([
                'ledger_account_id' => $la_id,
                'direction' => 'in',
                'amount' => $amt,
                'category' => 'PAYMENT',
                'description' => 'Payment #'.$id,
                'occurred_at' => date('Y-m-d H:i:s'),
                'source_type' => 'payment',
                'source_id' => $id,
                'created_by' => (int)$this->auth_user['id'],
            ]);
        }

        $this->db->trans_complete();

        audit_log($this,'payment_approve','Approve payment #'.$id);
        api_ok($this->PaymentModel->find_by_id($id));
    }

    public function reject(int $id = 0): void
    {
        $this->require_any_permission(['finance.verify','billing.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $pay = $this->PaymentModel->find_by_id($id);
        if (!$pay) { api_not_found(); return; }
        if (($pay['status'] ?? '') !== 'pending') {
            api_error('CONFLICT','Payment status bukan pending',409);
            return;
        }

        $in = $this->json_input();
        $note = $in['note'] ?? ($in['reason'] ?? null);

        $this->PaymentModel->reject($id, (int)$this->auth_user['id'], $note);
        audit_log($this,'payment_reject','Reject payment #'.$id);
        api_ok($this->PaymentModel->find_by_id($id));
    }

    private function _build_auto_allocations(array $pay): array
    {
        $payment_id = (int)($pay['id'] ?? 0);
        $payer_hh   = (int)($pay['payer_household_id'] ?? 0);
        $amount     = (float)($pay['amount'] ?? 0);

        if ($payment_id <= 0 || $amount <= 0) {
            return ['ok'=>false, 'message'=>'Payment tidak valid'];
        }

        $intents = $this->PaymentModel->list_intents($payment_id);
        if (empty($intents)) {
            return ['ok'=>false, 'message'=>'Tidak ada invoice intents pada pembayaran ini'];
        }

        $remainingPay = $amount;
        $invoice_allocs = [];

        foreach ($intents as $it) {
            $inv_id = (int)($it['invoice_id'] ?? 0);
            if ($inv_id <= 0) continue;

            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) continue;

            if ((int)($inv['household_id'] ?? 0) !== $payer_hh) {
                return ['ok'=>false, 'message'=>'Ada invoice intents yang bukan milik household pembayar'];
            }

            $invTotal = (float)($inv['total_amount'] ?? 0);
            if ($invTotal <= 0.0001) continue;

            $alreadyPaid = $this->PaymentModel->sum_allocated_for_invoice($inv_id);
            $remainInv = max(0.0, $invTotal - $alreadyPaid);

            if ($remainInv <= 0.0001) {
                continue; // already fully covered
            }

            $alloc = min($remainInv, $remainingPay);
            if ($alloc <= 0.0001) break;

            $invoice_allocs[] = [
                'invoice_id' => $inv_id,
                'allocated_amount' => round($alloc, 2),
            ];

            $remainingPay -= $alloc;
            if ($remainingPay <= 0.0001) break;
        }

        if ($remainingPay > 0.01) {
            return ['ok'=>false, 'message'=>'Nominal pembayaran lebih besar dari total sisa tagihan yang dipilih'];
        }

        $compMap = []; // cc_id => amount

        foreach ($invoice_allocs as $ia) {
            $inv_id = (int)$ia['invoice_id'];
            $allocAmt = (float)$ia['allocated_amount'];

            $inv = $this->InvoiceModel->find_by_id($inv_id);
            if (!$inv) continue;

            $invTotal = (float)($inv['total_amount'] ?? 0);
            $chargeTypeId = (int)($inv['charge_type_id'] ?? 0);
            if ($invTotal <= 0.0001 || $chargeTypeId <= 0) {
                return ['ok'=>false, 'message'=>'Invoice tidak punya charge_type_id / total_amount invalid'];
            }

            $components = $this->ChargeModel->list_components_by_charge_type($chargeTypeId);

            if (empty($components)) {
                return ['ok'=>false, 'message'=>'Komponen iuran belum diset untuk charge type invoice'];
            }

            $sumComp = 0.0;
            foreach ($components as $c) $sumComp += (float)($c['amount'] ?? 0);
            if ($sumComp <= 0.0001) {
                return ['ok'=>false, 'message'=>'Total komponen iuran 0'];
            }

            $running = 0.0;
            $n = count($components);

            for ($i=0; $i<$n; $i++) {
                $cc_id = (int)($components[$i]['id'] ?? 0);
                $cAmt  = (float)($components[$i]['amount'] ?? 0);
                if ($cc_id <= 0) continue;

                $portion = ($i === $n-1)
                    ? round($allocAmt - $running, 2)
                    : round($allocAmt * ($cAmt / $sumComp), 2);

                $running += $portion;

                if (!isset($compMap[$cc_id])) $compMap[$cc_id] = 0.0;
                $compMap[$cc_id] += $portion;
            }
        }

        $component_allocs = [];
        foreach ($compMap as $cc_id => $amt) {
            $amt = round((float)$amt, 2);
            if ($amt <= 0.0001) continue;
            $component_allocs[] = [
                'charge_component_id' => (int)$cc_id,
                'allocated_amount' => $amt,
            ];
        }

        $sumInv = 0.0; foreach ($invoice_allocs as $a) $sumInv += (float)$a['allocated_amount'];
        $sumComp = 0.0; foreach ($component_allocs as $a) $sumComp += (float)$a['allocated_amount'];

        if (abs($sumInv - (float)$amount) > 0.01) {
            return ['ok'=>false, 'message'=>'Auto alokasi invoice tidak pas dengan amount payment'];
        }
        if (abs($sumComp - (float)$amount) > 0.01) {
            return ['ok'=>false, 'message'=>'Auto alokasi komponen tidak pas dengan amount payment'];
        }

        return [
            'ok' => true,
            'invoice_allocations' => $invoice_allocs,
            'component_allocations' => $component_allocs,
        ];
    }
}
