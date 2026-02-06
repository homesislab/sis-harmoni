<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Billing extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Invoice_model','InvoiceModel');
        $this->load->model('Charge_model','ChargeModel');
    }

    public function generate(): void
    {
        $this->require_any_permission(['billing.manage']);

        $in = $this->json_input();
        $charge_type_id = (int)($in['charge_type_id'] ?? 0);
        $period = trim((string)($in['period'] ?? ''));
        $override_amount = array_key_exists('amount',$in) ? (float)$in['amount'] : null;

        if ($charge_type_id <= 0) { api_validation_error(['charge_type_id'=>'Wajib']); return; }
        if ($period === '') { api_validation_error(['period'=>'Wajib (YYYY-MM)']); return; }

        $ct = $this->ChargeModel->find_type($charge_type_id);
        if (!$ct) { api_not_found('Charge type tidak ditemukan'); return; }

        $default_amount = $override_amount !== null ? $override_amount : (float)$this->ChargeModel->sum_components($charge_type_id);

        $rows = $this->db->select('ho.house_id, ho.household_id, ho.occupancy_type, ho.start_date, ho.id')
            ->from('house_occupancies ho')
            ->where('ho.status','active')
            ->where('ho.household_id IS NOT NULL', null, false)
            ->where_in('ho.occupancy_type', ['tenant','owner_live'])
            ->order_by('ho.house_id','ASC')
            ->order_by("FIELD(ho.occupancy_type,'tenant','owner_live')", '', false)
            ->order_by('ho.start_date','DESC')
            ->order_by('ho.id','DESC')
            ->get()->result_array();

        $seen_house = [];
        $household_ids = [];
        foreach ($rows as $r) {
            $hid = (int)($r['house_id'] ?? 0);
            if ($hid <= 0 || isset($seen_house[$hid])) continue;
            $seen_house[$hid] = true;

            $hhid = (int)($r['household_id'] ?? 0);
            if ($hhid > 0) $household_ids[$hhid] = true;
        }
        $household_ids = array_keys($household_ids);

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($household_ids as $hhid) {
            $exists = $this->InvoiceModel->find_by_household_charge_period($hhid, $charge_type_id, $period);
            if ($exists) { $skipped++; continue; }

            $payload = [
                'household_id' => $hhid,
                'charge_type_id' => $charge_type_id,
                'period' => $period,
                'total_amount' => $default_amount,
                'status' => 'unpaid',
                'note' => 'Auto-generated',
            ];

            try {
                $id = $this->InvoiceModel->create($payload);
                $this->InvoiceModel->add_line($id, [
                    'house_id' => null,
                    'line_type' => 'base',
                    'description' => ($ct['name'] ?? 'Charge') . ' ' . $period,
                    'qty' => 1,
                    'unit_price' => $default_amount,
                    'amount' => $default_amount,
                    'sort_order' => 1,
                ]);
                $created++;
            } catch (Throwable $e) {
                $errors++;
            }
        }

        audit_log($this, 'billing_generate', 'Generate invoices ' . $period . ' charge_type=' . $charge_type_id);

        api_ok([
            'period' => $period,
            'charge_type_id' => $charge_type_id,
            'default_amount' => $default_amount,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}
