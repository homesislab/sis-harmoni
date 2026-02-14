<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Invoice_model', 'InvoiceModel');
        $this->load->model('Payment_model', 'PaymentModel');
        $this->load->model('Post_model', 'PostModel');
        $this->load->model('Event_model', 'EventModel');
        $this->load->model('Poll_model', 'PollModel');
    }

    /**
     * CI3 tidak punya ->when(). Helper ini untuk apply filter kondisional.
     *
     * @param CI_DB_query_builder $qb
     * @param bool $cond
     * @param callable $fn function(CI_DB_query_builder $q): void
     * @return CI_DB_query_builder
     */
    private function qb_if($qb, $cond, $fn)
    {
        if ($cond) $fn($qb);
        return $qb;
    }

    private function my_user_id(): int
    {
        if (isset($this->auth_user) && is_array($this->auth_user) && isset($this->auth_user['id'])) {
            return (int)$this->auth_user['id'];
        }
        if (isset($this->me) && is_array($this->me) && isset($this->me['id'])) {
            return (int)$this->me['id'];
        }
        if (isset($this->user) && is_array($this->user) && isset($this->user['id'])) {
            return (int)$this->user['id'];
        }
        if (method_exists($this, 'user_id')) {
            $id = (int)$this->user_id();
            if ($id > 0) return $id;
        }
        $sid = (int)$this->session->userdata('user_id');
        return $sid > 0 ? $sid : 0;
    }

    private function my_household_id(): int
    {
        $uid = $this->my_user_id();
        if ($uid <= 0) return 0;

        if ($this->db->table_exists('users') && $this->db->field_exists('household_id', 'users')) {
            $hh = (int)($this->db->select('household_id')
                ->from('users')
                ->where('id', $uid)
                ->get()->row()->household_id ?? 0);
            if ($hh > 0) return $hh;
        }

        $personId = 0;
        if ($this->db->table_exists('users') && $this->db->field_exists('person_id', 'users')) {
            $personId = (int)($this->db->select('person_id')
                ->from('users')
                ->where('id', $uid)
                ->get()->row()->person_id ?? 0);
        }

        if ($personId > 0 && $this->db->table_exists('persons')) {
            if ($this->db->field_exists('household_id', 'persons')) {
                $hh = (int)($this->db->select('household_id')
                    ->from('persons')
                    ->where('id', $personId)
                    ->get()->row()->household_id ?? 0);
                if ($hh > 0) return $hh;
            }

            if ($this->db->table_exists('households') && $this->db->field_exists('head_person_id', 'households')) {
                $hh = (int)($this->db->select('id')
                    ->from('households')
                    ->where('head_person_id', $personId)
                    ->order_by('id', 'desc')
                    ->limit(1)
                    ->get()->row()->id ?? 0);
                if ($hh > 0) return $hh;
            }
        }

        if ($personId > 0
            && $this->db->table_exists('house_occupancies')
            && $this->db->field_exists('person_id', 'house_occupancies')
            && $this->db->field_exists('household_id', 'house_occupancies')
        ) {
            $hh = (int)($this->db->select('household_id')
                ->from('house_occupancies')
                ->where('person_id', $personId)
                ->where('status', 'active')
                ->order_by('id', 'desc')
                ->limit(1)
                ->get()->row()->household_id ?? 0);
            if ($hh > 0) return $hh;
        }

        return 0;
    }

    public function summary(): void
    {
        $polls_open = (int)$this->db->from('polls')->where('status', 'published')->count_all_results();

        $events_upcoming = $this->db->from('events')
            ->where('event_at >=', date('Y-m-d H:i:s'))
            ->order_by('event_at', 'ASC')
            ->limit(5)
            ->get()->result_array();

        $posts_latest = $this->db->from('posts')
            ->where('status', 'published')
            ->order_by('created_at', 'DESC')
            ->limit(5)
            ->get()->result_array();

        $householdId = $this->my_household_id();

        $unpaid_amount = 0.0;
        $unpaid_invoices = 0;

        if ($householdId > 0 && $this->db->table_exists('invoices')) {
            $unpaid_amount = (float)($this->db->select('COALESCE(SUM(total_amount),0) AS s', false)
                ->from('invoices')
                ->where('household_id', $householdId)
                ->where_in('status', ['unpaid', 'partial'])
                ->get()->row()->s ?? 0);

            $unpaid_invoices = (int)($this->db->from('invoices')
                ->where('household_id', $householdId)
                ->where_in('status', ['unpaid', 'partial'])
                ->count_all_results());
        }

        api_ok([
            'polls_open'      => $polls_open,
            'events_upcoming' => $events_upcoming,
            'posts_latest'    => $posts_latest,

            'unpaid_amount'   => $unpaid_amount,
            'unpaid_invoices' => $unpaid_invoices,
        ]);
    }

    public function finance(): void
    {
        $now = date('Y-m-d H:i:s');
        $fromMonth = date('Y-m-01 00:00:00');

        $balance_paguyuban = (float)($this->db->select('COALESCE(SUM(balance),0) AS s', false)
            ->from('ledger_accounts')
            ->where('type', 'paguyuban')
            ->get()->row()->s ?? 0);

        $balance_dkm = (float)($this->db->select('COALESCE(SUM(balance),0) AS s', false)
            ->from('ledger_accounts')
            ->where('type', 'dkm')
            ->get()->row()->s ?? 0);

        $rows = $this->db->select('a.type, e.direction, COALESCE(SUM(e.amount),0) AS s', false)
            ->from('ledger_entries e')
            ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'inner')
            ->where('e.occurred_at >=', $fromMonth)
            ->where('e.occurred_at <=', $now)
            ->group_by(['a.type', 'e.direction'])
            ->get()->result_array();

        $income_month_paguyuban = 0.0;
        $expense_month_paguyuban = 0.0;
        $income_month_dkm = 0.0;
        $expense_month_dkm = 0.0;

        foreach ($rows as $r) {
            $t = $r['type'];
            $dir = $r['direction'];
            $sum = (float)($r['s'] ?? 0);
            if ($t === 'paguyuban') {
                if ($dir === 'in') $income_month_paguyuban = $sum;
                if ($dir === 'out') $expense_month_paguyuban = $sum;
            }
            if ($t === 'dkm') {
                if ($dir === 'in') $income_month_dkm = $sum;
                if ($dir === 'out') $expense_month_dkm = $sum;
            }
        }

        api_ok([
            'balance_paguyuban' => $balance_paguyuban,
            'balance_dkm'       => $balance_dkm,

            'income_month_paguyuban'  => $income_month_paguyuban,
            'expense_month_paguyuban' => $expense_month_paguyuban,
            'income_month_dkm'        => $income_month_dkm,
            'expense_month_dkm'       => $expense_month_dkm,

            'month_from' => $fromMonth,
            'month_to'   => $now,
        ]);
    }

    public function activity(): void
    {
        $items = [];
        if ($this->db->table_exists('audit_logs')) {
            $items = $this->db->select('*')->from('audit_logs')->order_by('id', 'desc')->limit(20)->get()->result_array();
        }
        api_ok($items);
    }

    public function report(): void
    {
        $this->require_permission('app.home.dashboard');

        $from = trim((string)$this->input->get('from'));
        $to = trim((string)$this->input->get('to'));
        $scope = strtolower(trim((string)$this->input->get('scope')));
        $orgUnitId = trim((string)$this->input->get('org_unit_id')); // opsional

        if ($scope !== 'dkm') $scope = 'paguyuban';

        if ($from === '' || $to === '') {
            $from = date('Y-m-01');
            $to = date('Y-m-d');
        }

        $fromDate = date('Y-m-d', strtotime($from));
        $toDate = date('Y-m-d', strtotime($to));

        $fromTs = $fromDate . ' 00:00:00';
        $toTs = $toDate . ' 23:59:59';

        $periods = $this->month_periods($fromDate, $toDate);

        $out = [
            'range' => [
                'from' => $fromDate,
                'to' => $toDate,
                'periods' => $periods,
            ],
            'scope' => $scope,
            'org_unit_id' => $orgUnitId !== '' ? $orgUnitId : null,
        ];

        $orgHouseIds = null;
        $orgHouseholdIds = null;

        if ($orgUnitId !== '') {
            if ($this->db->table_exists('houses') && $this->db->field_exists('org_unit_id', 'houses')) {
                $orgHouseIds = array_map('intval', array_column(
                    $this->db->select('id')->from('houses')->where('org_unit_id', $orgUnitId)->get()->result_array(),
                    'id'
                ));
            }

            if ($this->db->table_exists('households') && $this->db->field_exists('org_unit_id', 'households')) {
                $orgHouseholdIds = array_map('intval', array_column(
                    $this->db->select('id')->from('households')->where('org_unit_id', $orgUnitId)->get()->result_array(),
                    'id'
                ));
            }

            if ($orgHouseholdIds === null
                && $this->db->table_exists('house_occupancies')
                && $this->db->table_exists('houses')
                && $this->db->field_exists('org_unit_id', 'houses')
                && $this->db->field_exists('household_id', 'house_occupancies')
                && $this->db->field_exists('house_id', 'house_occupancies')
            ) {
                $orgHouseholdIds = array_map('intval', array_column(
                    $this->db->select('DISTINCT ho.household_id AS id', false)
                        ->from('house_occupancies ho')
                        ->join('houses hs', 'hs.id = ho.house_id', 'inner')
                        ->where('ho.status', 'active')
                        ->where('hs.org_unit_id', $orgUnitId)
                        ->get()->result_array(),
                    'id'
                ));
            }

            if ($orgHouseIds === null
                && $this->db->table_exists('houses')
                && $this->db->field_exists('org_unit_id', 'houses')
            ) {
                $orgHouseIds = [];
            }
        }

        $financeSummary = [
            'balance' => 0.0,
            'income' => 0.0,
            'expense' => 0.0,
            'cashflow_monthly' => [],
            'expense_categories' => [],
        ];

        $filterLedgerByOrg = ($orgUnitId !== '' && $this->db->field_exists('org_unit_id', 'ledger_accounts'));

        if ($this->has_permission('app.home.dashboard.widget.finance') || $this->has_permission('app.home.dashboard.widget.ledger')) {
            $qbBal = $this->db->select('COALESCE(SUM(balance),0) AS s', false)
                ->from('ledger_accounts')
                ->where('type', $scope)
                ->where('deleted_at IS NULL', null, false);

            $this->qb_if($qbBal, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                $q->where('org_unit_id', $orgUnitId);
            });

            $balance = (float)($qbBal->get()->row()->s ?? 0);

            $qbInOut = $this->db->select('e.direction, COALESCE(SUM(e.amount),0) AS s', false)
                ->from('ledger_entries e')
                ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'inner')
                ->where('a.type', $scope)
                ->where('a.deleted_at IS NULL', null, false)
                ->where('e.occurred_at >=', $fromTs)
                ->where('e.occurred_at <=', $toTs)
                ->group_by('e.direction');

            $this->qb_if($qbInOut, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                $q->where('a.org_unit_id', $orgUnitId);
            });

            $rowsInOut = $qbInOut->get()->result_array();

            $income = 0.0;
            $expense = 0.0;
            foreach ($rowsInOut as $r) {
                if (($r['direction'] ?? '') === 'in') $income = (float)($r['s'] ?? 0);
                if (($r['direction'] ?? '') === 'out') $expense = (float)($r['s'] ?? 0);
            }

            $qbCash = $this->db->select("DATE_FORMAT(e.occurred_at,'%Y-%m') AS ym, e.direction, COALESCE(SUM(e.amount),0) AS s", false)
                ->from('ledger_entries e')
                ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'inner')
                ->where('a.type', $scope)
                ->where('a.deleted_at IS NULL', null, false)
                ->where('e.occurred_at >=', $fromTs)
                ->where('e.occurred_at <=', $toTs)
                ->group_by(['ym', 'e.direction'])
                ->order_by('ym', 'asc');

            $this->qb_if($qbCash, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                $q->where('a.org_unit_id', $orgUnitId);
            });

            $cashflow = $qbCash->get()->result_array();

            $cashflowMonthly = [];
            foreach ($cashflow as $r) {
                $ym = (string)($r['ym'] ?? '');
                if ($ym === '') continue;
                if (!isset($cashflowMonthly[$ym])) {
                    $cashflowMonthly[$ym] = ['month' => $ym, 'in' => 0.0, 'out' => 0.0];
                }
                $dir = (string)($r['direction'] ?? '');
                $sum = (float)($r['s'] ?? 0);
                if ($dir === 'in') $cashflowMonthly[$ym]['in'] = $sum;
                if ($dir === 'out') $cashflowMonthly[$ym]['out'] = $sum;
            }

            $qbCats = $this->db->select('COALESCE(e.category, "Lainnya") AS category, COALESCE(SUM(e.amount),0) AS amount', false)
                ->from('ledger_entries e')
                ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'inner')
                ->where('a.type', $scope)
                ->where('a.deleted_at IS NULL', null, false)
                ->where('e.direction', 'out')
                ->where('e.occurred_at >=', $fromTs)
                ->where('e.occurred_at <=', $toTs)
                ->group_by('category')
                ->order_by('amount', 'desc')
                ->limit(5);

            $this->qb_if($qbCats, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                $q->where('a.org_unit_id', $orgUnitId);
            });

            $expenseCats = $qbCats->get()->result_array();

            $financeSummary = [
                'balance' => $balance,
                'income' => $income,
                'expense' => $expense,
                'cashflow_monthly' => array_values($cashflowMonthly),
                'expense_categories' => $expenseCats,
            ];

            $out['finance'] = $financeSummary;

            if ($this->has_permission('app.home.dashboard.widget.ledger')) {
                $qbAcc = $this->db->select('id, name, type, balance')
                    ->from('ledger_accounts')
                    ->where('type', $scope)
                    ->where('deleted_at IS NULL', null, false)
                    ->order_by('name', 'asc');

                $this->qb_if($qbAcc, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                    $q->where('org_unit_id', $orgUnitId);
                });

                $accounts = $qbAcc->get()->result_array();

                $qbEnt = $this->db->select('e.id, e.direction, e.amount, e.category, e.description, e.occurred_at, e.source_type, e.source_id, a.name AS ledger_account_name')
                    ->from('ledger_entries e')
                    ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'inner')
                    ->where('a.type', $scope)
                    ->where('a.deleted_at IS NULL', null, false)
                    ->where('e.occurred_at >=', $fromTs)
                    ->where('e.occurred_at <=', $toTs)
                    ->order_by('e.occurred_at', 'desc')
                    ->limit(10);

                $this->qb_if($qbEnt, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                    $q->where('a.org_unit_id', $orgUnitId);
                });

                $entries = $qbEnt->get()->result_array();

                $qbCnt = $this->db->from('ledger_entries e')
                    ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'inner')
                    ->where('a.type', $scope)
                    ->where('a.deleted_at IS NULL', null, false)
                    ->where('e.occurred_at >=', $fromTs)
                    ->where('e.occurred_at <=', $toTs);

                $this->qb_if($qbCnt, $filterLedgerByOrg, function($q) use ($orgUnitId) {
                    $q->where('a.org_unit_id', $orgUnitId);
                });

                $entriesCount = (int)$qbCnt->count_all_results();

                $out['ledger'] = [
                    'balance_scope' => $balance,
                    'income' => $income,
                    'expense' => $expense,
                    'entries_count' => $entriesCount,
                    'accounts' => $accounts,
                    'entries_latest' => $entries,
                ];
            }
        }

        $billingSummary = [
            'invoice_total' => 0,
            'invoice_paid' => 0,
            'payment_rate' => 0.0,
            'unpaid_amount' => 0.0,
            'unpaid_invoices' => 0,
            'unpaid_households' => 0,
            'avg_paid_per_household' => 0.0,
            'invoices_by_status' => [],
            'aging' => [],
            'unpaid_top' => [],
            'invoices_latest' => [],
            'payments_pending' => 0,
            'payments_pending_latest' => [],
            'payments_latest' => [],
        ];

        if ($this->has_permission('app.home.dashboard.widget.billing')) {
            $useHHFilter = is_array($orgHouseholdIds);

            $invoiceTitleSelect = "'Tagihan' AS invoice_title";
            $joinChargeTypes = false;

            if ($this->db->field_exists('title', 'invoices')) {
                $invoiceTitleSelect = "COALESCE(NULLIF(i.title,''),'Tagihan') AS invoice_title";
            } elseif ($this->db->field_exists('charge_type_id', 'invoices') && $this->db->table_exists('charge_types')) {
                $invoiceTitleSelect = "COALESCE(NULLIF(ct.name,''),'Tagihan') AS invoice_title";
                $joinChargeTypes = true;
            }

            $qbRows = $this->db->select('status, COUNT(*) AS c', false)
                ->from('invoices')
                ->where_in('period', $periods)
                ->group_by('status');

            $this->qb_if($qbRows, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $rows = $qbRows->get()->result_array();

            $statusMap = [
                'unpaid' => 0,
                'partial' => 0,
                'paid' => 0,
                'void' => 0,
            ];
            foreach ($rows as $r) {
                $st = (string)($r['status'] ?? '');
                if ($st === '' || !isset($statusMap[$st])) continue;
                $statusMap[$st] = (int)($r['c'] ?? 0);
            }

            $qbTotal = $this->db->from('invoices')->where_in('period', $periods);
            $this->qb_if($qbTotal, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $invoiceTotal = (int)$qbTotal->count_all_results();

            $qbPaid = $this->db->from('invoices')
                ->where('status', 'paid')
                ->where_in('period', $periods);
            $this->qb_if($qbPaid, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $invoicePaid = (int)$qbPaid->count_all_results();

            $paymentRate = $invoiceTotal > 0 ? round(($invoicePaid / $invoiceTotal) * 100, 2) : 0.0;

            $qbUnpaidInv = $this->db->from('invoices')
                ->where_in('status', ['unpaid', 'partial'])
                ->where_in('period', $periods);
            $this->qb_if($qbUnpaidInv, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $unpaidInvoices = (int)$qbUnpaidInv->count_all_results();

            $qbUnpaidAmt = $this->db->select('COALESCE(SUM(total_amount),0) AS s', false)
                ->from('invoices')
                ->where_in('status', ['unpaid', 'partial'])
                ->where_in('period', $periods);
            $this->qb_if($qbUnpaidAmt, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $unpaidAmount = (float)($qbUnpaidAmt->get()->row()->s ?? 0);

            $qbUnpaidHH = $this->db->select('COUNT(DISTINCT household_id) AS c', false)
                ->from('invoices')
                ->where_in('status', ['unpaid', 'partial'])
                ->where_in('period', $periods);
            $this->qb_if($qbUnpaidHH, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $unpaidHouseholds = (int)($qbUnpaidHH->get()->row()->c ?? 0);

            $qbLatestInv = $this->db
                ->select("i.id, i.household_id, h.kk_number, hs.block, hs.number, p.full_name AS head_name, i.period, i.total_amount, i.status, i.created_at, {$invoiceTitleSelect}", false)
                ->from('invoices i')
                ->join('households h', 'h.id = i.household_id', 'left')
                ->join('persons p', 'p.id = h.head_person_id', 'left')
                ->join('house_occupancies ho', "ho.household_id = h.id AND ho.status = 'active'", 'left', false)
                ->join('houses hs', 'hs.id = ho.house_id', 'left');

            if ($joinChargeTypes) {
                $qbLatestInv->join('charge_types ct', 'ct.id = i.charge_type_id', 'left');
            }

            $qbLatestInv
                ->where_in('i.period', $periods)
                ->order_by('i.id', 'desc')
                ->limit(5);

            $this->qb_if($qbLatestInv, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('i.household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $qbLatestInv->group_by('i.id');
            $latestInvoices = $qbLatestInv->get()->result_array();

            $qbTopUnpaid = $this->db
                ->select("i.id, i.household_id, h.kk_number, hs.block, hs.number, p.full_name AS head_name, i.period, i.total_amount, i.status, i.created_at, {$invoiceTitleSelect}", false)
                ->from('invoices i')
                ->join('households h', 'h.id = i.household_id', 'left')
                ->join('persons p', 'p.id = h.head_person_id', 'left')
                ->join('house_occupancies ho', "ho.household_id = h.id AND ho.status = 'active'", 'left', false)
                ->join('houses hs', 'hs.id = ho.house_id', 'left');

            if ($joinChargeTypes) {
                $qbTopUnpaid->join('charge_types ct', 'ct.id = i.charge_type_id', 'left');
            }

            $qbTopUnpaid
                ->where_in('i.status', ['unpaid', 'partial'])
                ->where_in('i.period', $periods)
                ->order_by('i.total_amount', 'desc')
                ->limit(5);

            $this->qb_if($qbTopUnpaid, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('i.household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $qbTopUnpaid->group_by('i.id');
            $topUnpaid = $qbTopUnpaid->get()->result_array();

            $aging = $this->billing_aging_all($useHHFilter ? $orgHouseholdIds : null);

            $qbTrend = $this->db->select('period, COALESCE(SUM(total_amount),0) AS amount', false)
                ->from('invoices')
                ->where_in('period', $periods)
                ->where_in('status', ['unpaid', 'partial'])
                ->group_by('period')
                ->order_by('period', 'asc');
            $this->qb_if($qbTrend, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $unpaidTrendRows = $qbTrend->get()->result_array();

            $unpaidTrend = [];
            foreach ($periods as $p) {
                $unpaidTrend[$p] = ['month' => $p, 'amount' => 0.0];
            }
            foreach ($unpaidTrendRows as $r) {
                $p = (string)($r['period'] ?? '');
                if ($p === '' || !isset($unpaidTrend[$p])) continue;
                $unpaidTrend[$p]['amount'] = (float)($r['amount'] ?? 0);
            }

            $qbStMonth = $this->db->select('period, status, COUNT(*) AS c', false)
                ->from('invoices')
                ->where_in('period', $periods)
                ->group_by(['period', 'status'])
                ->order_by('period', 'asc');
            $this->qb_if($qbStMonth, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $statusMonthlyRows = $qbStMonth->get()->result_array();

            $statusMonthly = [];
            foreach ($periods as $p) {
                $statusMonthly[$p] = ['month' => $p, 'unpaid' => 0, 'partial' => 0, 'paid' => 0, 'void' => 0];
            }
            foreach ($statusMonthlyRows as $r) {
                $p = (string)($r['period'] ?? '');
                $st = (string)($r['status'] ?? '');
                if ($p === '' || $st === '' || !isset($statusMonthly[$p]) || !isset($statusMonthly[$p][$st])) continue;
                $statusMonthly[$p][$st] = (int)($r['c'] ?? 0);
            }

            $qbTopHH = $this->db
                ->select('i.household_id, h.kk_number, hs.block, hs.number, p.full_name AS head_name, COUNT(*) AS invoices_count, COALESCE(SUM(i.total_amount),0) AS amount', false)
                ->from('invoices i')
                ->join('households h', 'h.id = i.household_id', 'left')
                ->join('persons p', 'p.id = h.head_person_id', 'left')
                ->join('house_occupancies ho', "ho.household_id = h.id AND ho.status = 'active'", 'left', false)
                ->join('houses hs', 'hs.id = ho.house_id', 'left')
                ->where_in('i.period', $periods)
                ->where_in('i.status', ['unpaid', 'partial'])
                ->group_by('i.household_id')
                ->order_by('amount', 'desc')
                ->limit(10);

            $this->qb_if($qbTopHH, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('i.household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $topHouseholds = $qbTopHH->get()->result_array();

            $qbPayPend = $this->db->from('payments')
                ->where('status', 'pending')
                ->where('paid_at >=', $fromTs)
                ->where('paid_at <=', $toTs);

            $this->qb_if($qbPayPend, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('payer_household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $paymentsPending = (int)$qbPayPend->count_all_results();

            $allocExists = $this->db->table_exists('payment_invoice_allocations');
            $invExists   = $this->db->table_exists('invoices');

            $invTitleExpr = "'Tagihan'";
            $joinCt2 = false;
            if ($invExists) {
                if ($this->db->field_exists('title', 'invoices')) {
                    $invTitleExpr = "COALESCE(NULLIF(inv.title,''),'Tagihan')";
                } elseif ($this->db->field_exists('charge_type_id', 'invoices') && $this->db->table_exists('charge_types')) {
                    $invTitleExpr = "COALESCE(NULLIF(ct2.name,''),'Tagihan')";
                    $joinCt2 = true;
                }
            }

            $qbPayPendLatest = $this->db
                ->select("
                    p.id,
                    p.payer_household_id,
                    h.kk_number,
                    hs.block,
                    hs.number,
                    pe.full_name AS head_name,
                    p.amount,
                    p.paid_at,
                    p.status,
                    p.source,
                    " . ($allocExists && $invExists ? "COUNT(DISTINCT pia.invoice_id) AS invoices_count," : "0 AS invoices_count,") . "
                    " . ($allocExists && $invExists ? "GROUP_CONCAT(DISTINCT {$invTitleExpr} ORDER BY {$invTitleExpr} SEPARATOR ' • ') AS inv_titles," : "NULL AS inv_titles,") . "
                    " . ($allocExists && $invExists ? "GROUP_CONCAT(DISTINCT inv.period ORDER BY inv.period DESC SEPARATOR ',') AS inv_periods" : "NULL AS inv_periods") . "
                ", false)
                ->from('payments p')
                ->join('households h', 'h.id = p.payer_household_id', 'left')
                ->join('persons pe', 'pe.id = h.head_person_id', 'left')
                ->join('house_occupancies ho', "ho.household_id = h.id AND ho.status = 'active'", 'left', false)
                ->join('houses hs', 'hs.id = ho.house_id', 'left')
                ->where('p.status', 'pending')
                ->where('p.paid_at >=', $fromTs)
                ->where('p.paid_at <=', $toTs);

            if ($allocExists && $invExists) {
                $qbPayPendLatest->join('payment_invoice_allocations pia', 'pia.payment_id = p.id', 'left');
                $qbPayPendLatest->join('invoices inv', 'inv.id = pia.invoice_id', 'left');
                if ($joinCt2) $qbPayPendLatest->join('charge_types ct2', 'ct2.id = inv.charge_type_id', 'left');
            }

            $this->qb_if($qbPayPendLatest, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('p.payer_household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $qbPayPendLatest
                ->group_by('p.id')
                ->order_by('p.paid_at', 'desc')
                ->limit(5);

            $paymentsPendingLatest = $qbPayPendLatest->get()->result_array();

            $qbPayLatest = $this->db
                ->select("
                    p.id,
                    p.payer_household_id,
                    h.kk_number,
                    hs.block,
                    hs.number,
                    pe.full_name AS head_name,
                    p.amount,
                    p.paid_at,
                    p.status,
                    p.source,
                    " . ($allocExists && $invExists ? "COUNT(DISTINCT pia.invoice_id) AS invoices_count," : "0 AS invoices_count,") . "
                    " . ($allocExists && $invExists ? "GROUP_CONCAT(DISTINCT {$invTitleExpr} ORDER BY {$invTitleExpr} SEPARATOR ' • ') AS inv_titles," : "NULL AS inv_titles,") . "
                    " . ($allocExists && $invExists ? "GROUP_CONCAT(DISTINCT inv.period ORDER BY inv.period DESC SEPARATOR ',') AS inv_periods" : "NULL AS inv_periods") . "
                ", false)
                ->from('payments p')
                ->join('households h', 'h.id = p.payer_household_id', 'left')
                ->join('persons pe', 'pe.id = h.head_person_id', 'left')
                ->join('house_occupancies ho', "ho.household_id = h.id AND ho.status = 'active'", 'left', false)
                ->join('houses hs', 'hs.id = ho.house_id', 'left');

            if ($allocExists && $invExists) {
                $qbPayLatest->join('payment_invoice_allocations pia', 'pia.payment_id = p.id', 'left');
                $qbPayLatest->join('invoices inv', 'inv.id = pia.invoice_id', 'left');
                if ($joinCt2) $qbPayLatest->join('charge_types ct2', 'ct2.id = inv.charge_type_id', 'left');
            }

            $qbPayLatest
                ->where('p.paid_at >=', $fromTs)
                ->where('p.paid_at <=', $toTs)
                ->order_by('p.paid_at', 'desc')
                ->limit(5);

            $this->qb_if($qbPayLatest, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('p.payer_household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $qbPayLatest->group_by('p.id');
            $latestPayments = $qbPayLatest->get()->result_array();

            $qbPaidAmt = $this->db->select('COALESCE(SUM(amount),0) AS s', false)
                ->from('payments')
                ->where('status', 'approved')
                ->where('paid_at >=', $fromTs)
                ->where('paid_at <=', $toTs);

            $this->qb_if($qbPaidAmt, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('payer_household_id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });

            $paidAmount = (float)($qbPaidAmt->get()->row()->s ?? 0);

            $qbHHCount = $this->db->from('households');
            $this->qb_if($qbHHCount, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $totalHouseholds = (int)$qbHHCount->count_all_results();

            $avgPaidPerHousehold = $totalHouseholds > 0 ? round($paidAmount / $totalHouseholds, 2) : 0.0;

            $normHousehold = function($kk, $block = null, $number = null, $headName = null) {
                $kk = (string)($kk ?? '');
                $block = strtoupper(trim((string)($block ?? '')));
                $number = trim((string)($number ?? ''));
                $headName = trim((string)($headName ?? ''));

                $unit = ($block !== '' && $number !== '') ? ($block . '-' . $number) : '';
                if ($unit === '' && $kk !== '') $unit = 'KK ' . $kk;
                if ($unit === '') $unit = 'KK';

                $headShort = $headName !== '' ? preg_split('/\s+/', $headName)[0] : '';

                return [
                    'unit' => $unit,
                    'label' => ($headName !== '') ? ($unit . ' • ' . $headName) : $unit,
                    'head_short' => $headShort,
                ];
            };

            foreach ($latestInvoices as &$it) {
                $n = $normHousehold($it['kk_number'] ?? '', $it['block'] ?? null, $it['number'] ?? null, $it['head_name'] ?? null);
                $it['household_label'] = $n['label'];
                $it['household_unit'] = $n['unit'];
                $it['head_name_short'] = $n['head_short'];
            }
            unset($it);

            foreach ($topUnpaid as &$it) {
                $n = $normHousehold($it['kk_number'] ?? '', $it['block'] ?? null, $it['number'] ?? null, $it['head_name'] ?? null);
                $it['household_label'] = $n['label'];
                $it['household_unit'] = $n['unit'];
                $it['head_name_short'] = $n['head_short'];
            }
            unset($it);

            foreach ($latestPayments as &$it) {
                $n = $normHousehold($it['kk_number'] ?? '', $it['block'] ?? null, $it['number'] ?? null, $it['head_name'] ?? null);
                $it['household_label'] = $n['label'];
                $it['household_unit'] = $n['unit'];
                $it['head_name_short'] = $n['head_short'];

                $cnt = (int)($it['invoices_count'] ?? 0);
                $titlesRaw = trim((string)($it['inv_titles'] ?? ''));
                $periodsRaw = trim((string)($it['inv_periods'] ?? ''));

                $titles = $titlesRaw !== '' ? array_values(array_unique(array_map('trim', explode(' • ', $titlesRaw)))) : [];
                $periods = $periodsRaw !== '' ? array_values(array_unique(array_map('trim', explode(',', $periodsRaw)))) : [];

                if ($cnt <= 0) {
                    $it['payment_for_title'] = 'Tagihan';
                    $it['payment_for_period'] = null;
                } elseif ($cnt === 1) {
                    $it['payment_for_title'] = $titles[0] ?? 'Tagihan';
                    $it['payment_for_period'] = $periods[0] ?? null;
                } else {
                    $baseTitle = (count($titles) === 1 ? ($titles[0] ?? 'Tagihan') : 'Gabungan');
                    $it['payment_for_title'] = $baseTitle . " ({$cnt} tagihan)";
                    $it['payment_for_period'] = (count($periods) === 1 ? ($periods[0] ?? null) : null);
                }
            }
            unset($it);

            foreach ($paymentsPendingLatest as &$it) {
                $n = $normHousehold($it['kk_number'] ?? '', $it['block'] ?? null, $it['number'] ?? null, $it['head_name'] ?? null);
                $it['household_label'] = $n['label'];
                $it['household_unit'] = $n['unit'];
                $it['head_name_short'] = $n['head_short'];

                $cnt = (int)($it['invoices_count'] ?? 0);
                $titlesRaw = trim((string)($it['inv_titles'] ?? ''));
                $periodsRaw = trim((string)($it['inv_periods'] ?? ''));

                $titles = $titlesRaw !== '' ? array_values(array_unique(array_map('trim', explode(' • ', $titlesRaw)))) : [];
                $periods = $periodsRaw !== '' ? array_values(array_unique(array_map('trim', explode(',', $periodsRaw)))) : [];

                if ($cnt <= 0) {
                    $it['payment_for_title'] = 'Tagihan';
                    $it['payment_for_period'] = null;
                } elseif ($cnt === 1) {
                    $it['payment_for_title'] = $titles[0] ?? 'Tagihan';
                    $it['payment_for_period'] = $periods[0] ?? null;
                } else {
                    $baseTitle = (count($titles) === 1 ? ($titles[0] ?? 'Tagihan') : 'Gabungan');
                    $it['payment_for_title'] = $baseTitle . " ({$cnt} tagihan)";
                    $it['payment_for_period'] = (count($periods) === 1 ? ($periods[0] ?? null) : null);
                }
            }
            unset($it);

            foreach ($topHouseholds as &$it) {
                $n = $normHousehold($it['kk_number'] ?? '', $it['block'] ?? null, $it['number'] ?? null, $it['head_name'] ?? null);
                $it['household_label'] = $n['label'];
                $it['household_unit'] = $n['unit'];
                $it['head_name_short'] = $n['head_short'];
            }
            unset($it);

            $billingSummary = [
                'invoice_total' => $invoiceTotal,
                'invoice_paid' => $invoicePaid,
                'payment_rate' => $paymentRate,
                'unpaid_amount' => $unpaidAmount,
                'unpaid_invoices' => $unpaidInvoices,
                'unpaid_households' => $unpaidHouseholds,
                'avg_paid_per_household' => $avgPaidPerHousehold,

                'total_invoices' => $invoiceTotal,
                'paid_invoices' => $invoicePaid,
                'payment_rate_percent' => $paymentRate,

                'invoices_by_status' => $statusMap,
                'invoices_by_status_rows' => $rows,

                'aging' => $aging,
                'aging_unpaid' => $aging,

                'unpaid_top' => $topUnpaid,
                'invoices_latest' => $latestInvoices,

                'payments_pending' => $paymentsPending,
                'payments_pending_latest' => $paymentsPendingLatest,
                'payments_latest' => $latestPayments,

                'unpaid_trend_monthly' => array_values($unpaidTrend),
                'invoices_status_monthly' => array_values($statusMonthly),
                'unpaid_top_households' => $topHouseholds,
            ];

            $out['billing'] = $billingSummary;
        }

        $out['overview'] = [
            'balance' => (float)($financeSummary['balance'] ?? 0),
            'income' => (float)($financeSummary['income'] ?? 0),
            'expense' => (float)($financeSummary['expense'] ?? 0),

            'unpaid_amount' => (float)($billingSummary['unpaid_amount'] ?? 0),
            'unpaid_invoices' => (int)($billingSummary['unpaid_invoices'] ?? 0),
            'unpaid_households' => (int)($billingSummary['unpaid_households'] ?? 0),

            'invoice_total' => (int)($billingSummary['invoice_total'] ?? 0),
            'invoice_paid' => (int)($billingSummary['invoice_paid'] ?? 0),
            'payment_rate' => (float)($billingSummary['payment_rate'] ?? 0),

            'unpaid_trend_monthly' => $billingSummary['unpaid_trend_monthly'] ?? [],
            'invoices_status_monthly' => $billingSummary['invoices_status_monthly'] ?? [],
        ];

        if ($this->has_permission('app.home.dashboard.widget.residents')) {
            $useHouseFilter = is_array($orgHouseIds);
            $useHHFilter = is_array($orgHouseholdIds);

            $qbHT = $this->db->from('houses');
            $this->qb_if($qbHT, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $housesTotal = (int)$qbHT->count_all_results();

            $qbHV = $this->db->from('houses')->where('status', 'vacant');
            $this->qb_if($qbHV, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $housesVacant = (int)$qbHV->count_all_results();

            $qbHI = $this->db->from('houses')->where_in('status', ['occupied', 'rented', 'owned']);
            $this->qb_if($qbHI, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $housesInhabited = (int)$qbHI->count_all_results();

            $qbHHT = $this->db->from('households');
            $this->qb_if($qbHHT, $useHHFilter, function($q) use ($orgHouseholdIds) {
                if (!empty($orgHouseholdIds)) $q->where_in('id', $orgHouseholdIds);
                else $q->where('1=0', null, false);
            });
            $householdsTotal = (int)$qbHHT->count_all_results();

            $personsTotal = (int)$this->db->from('persons')->where('status', 'active')->count_all_results();

            $qbHBS = $this->db->select('status, COUNT(*) AS c', false)
                ->from('houses')
                ->group_by('status')
                ->order_by('c', 'desc');
            $this->qb_if($qbHBS, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $housesByStatus = $qbHBS->get()->result_array();

            $claims = [];
            if ($this->db->table_exists('house_claims')) {
                $qbClaims = $this->db->select('c.id, c.house_id, hs.code AS house_code, c.claim_type, c.status, c.requested_at')
                    ->from('house_claims c')
                    ->join('houses hs', 'hs.id = c.house_id', 'left')
                    ->where('c.status', 'pending')
                    ->order_by('c.id', 'desc')
                    ->limit(10);

                $this->qb_if($qbClaims, $useHouseFilter, function($q) use ($orgHouseIds) {
                    if (!empty($orgHouseIds)) $q->where_in('c.house_id', $orgHouseIds);
                    else $q->where('1=0', null, false);
                });

                $claims = $qbClaims->get()->result_array();
            }

            $occupancies = [];
            if ($this->db->table_exists('house_occupancies')) {
                $qbOcc = $this->db->select('o.id, o.house_id, hs.code AS house_code, o.household_id, h.kk_number, o.status, o.start_date, o.end_date, o.created_at')
                    ->from('house_occupancies o')
                    ->join('houses hs', 'hs.id = o.house_id', 'left')
                    ->join('households h', 'h.id = o.household_id', 'left')
                    ->order_by('o.id', 'desc')
                    ->limit(10);

                $this->qb_if($qbOcc, $useHouseFilter, function($q) use ($orgHouseIds) {
                    if (!empty($orgHouseIds)) $q->where_in('o.house_id', $orgHouseIds);
                    else $q->where('1=0', null, false);
                });
                $this->qb_if($qbOcc, $useHHFilter, function($q) use ($orgHouseholdIds) {
                    if (!empty($orgHouseholdIds)) $q->where_in('o.household_id', $orgHouseholdIds);
                    else $q->where('1=0', null, false);
                });

                $occupancies = $qbOcc->get()->result_array();
            }

            foreach ($claims as &$c) {
                $c['house_label'] = ($c['house_code'] ?? '') !== '' ? ('Rumah ' . $c['house_code']) : 'Rumah';
            }
            unset($c);

            foreach ($occupancies as &$o) {
                $o['house_label'] = ($o['house_code'] ?? '') !== '' ? ('Rumah ' . $o['house_code']) : 'Rumah';
                $o['household_label'] = ($o['kk_number'] ?? '') !== '' ? ('KK ' . $o['kk_number']) : 'KK';
            }
            unset($o);

            $out['residents'] = [
                'houses_total' => $housesTotal,
                'houses_vacant' => $housesVacant,
                'houses_inhabited' => $housesInhabited,
                'households_total' => $householdsTotal,
                'persons_total' => $personsTotal,

                'houses_by_status' => $housesByStatus,

                'house_claims_pending' => count($claims),
                'house_claims_latest' => $claims,
                'occupancies_latest' => $occupancies,
            ];
        }

        if ($this->has_permission('app.home.dashboard.widget.security')) {
            $useHouseFilter = is_array($orgHouseIds);

            $qbEmOpen = $this->db->from('emergency_reports')
                ->where_in('status', ['open', 'acknowledged']);
            $this->qb_if($qbEmOpen, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('house_id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $emOpen = (int)$qbEmOpen->count_all_results();

            $qbEmLatest = $this->db->select('e.id, e.type, e.status, e.created_at, hs.code AS house_code')
                ->from('emergency_reports e')
                ->join('houses hs', 'hs.id = e.house_id', 'left')
                ->order_by('e.id', 'desc')
                ->limit(10);
            $this->qb_if($qbEmLatest, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('e.house_id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $emLatest = $qbEmLatest->get()->result_array();

            $qbEmMonthly = $this->db->select("DATE_FORMAT(e.created_at,'%Y-%m') AS ym, COUNT(*) AS c", false)
                ->from('emergency_reports e')
                ->where('e.created_at >=', $fromTs)
                ->where('e.created_at <=', $toTs)
                ->group_by('ym')
                ->order_by('ym', 'asc');
            $this->qb_if($qbEmMonthly, $useHouseFilter, function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('e.house_id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $emMonthlyRows = $qbEmMonthly->get()->result_array();

            $emMonthly = [];
            foreach ($periods as $p) $emMonthly[$p] = ['month' => $p, 'count' => 0];
            foreach ($emMonthlyRows as $r) {
                $ym = (string)($r['ym'] ?? '');
                if ($ym !== '' && isset($emMonthly[$ym])) $emMonthly[$ym]['count'] = (int)($r['c'] ?? 0);
            }

            foreach ($emLatest as &$e) {
                $e['house_label'] = ($e['house_code'] ?? '') !== '' ? ('Rumah ' . $e['house_code']) : '';
                $e['title'] = $e['type'] ?? '';
            }
            unset($e);

            $qbFbOpen = $this->db->from('feedbacks')->where_in('status', ['open', 'in_review']);
            $this->qb_if($qbFbOpen, $useHouseFilter && $this->db->field_exists('house_id', 'feedbacks'), function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('house_id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });
            $fbOpen = (int)$qbFbOpen->count_all_results();

            $fbTopCats = [];
            if ($this->db->table_exists('feedback_categories')) {
                $qbFbTop = $this->db->select('COALESCE(c.name, "Lainnya") AS name, COUNT(*) AS c', false)
                    ->from('feedbacks f')
                    ->join('feedback_categories c', 'c.id = f.category_id', 'left')
                    ->where('f.created_at >=', $fromTs)
                    ->where('f.created_at <=', $toTs)
                    ->group_by('name')
                    ->order_by('c', 'desc')
                    ->limit(5);

                $this->qb_if($qbFbTop, $useHouseFilter && $this->db->field_exists('house_id', 'feedbacks'), function($q) use ($orgHouseIds) {
                    if (!empty($orgHouseIds)) $q->where_in('f.house_id', $orgHouseIds);
                    else $q->where('1=0', null, false);
                });

                $fbTopCats = $qbFbTop->get()->result_array();
            }

            $qbFbLatest = $this->db->select('f.id, f.title, f.status, f.created_at, c.name AS category_name')
                ->from('feedbacks f')
                ->join('feedback_categories c', 'c.id = f.category_id', 'left')
                ->order_by('f.id', 'desc')
                ->limit(10);

            $this->qb_if($qbFbLatest, $useHouseFilter && $this->db->field_exists('house_id', 'feedbacks'), function($q) use ($orgHouseIds) {
                if (!empty($orgHouseIds)) $q->where_in('f.house_id', $orgHouseIds);
                else $q->where('1=0', null, false);
            });

            $fbLatest = $qbFbLatest->get()->result_array();

            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            $weekStart = date('Y-m-d 00:00:00', strtotime('-6 days'));

            $guestToday = 0;
            $guestWeek = 0;
            $guestLatest = [];
            if ($this->db->table_exists('guest_visits')) {
                $qbGT = $this->db->from('guest_visits')
                    ->where('visit_at >=', $todayStart)
                    ->where('visit_at <=', $todayEnd);
                $this->qb_if($qbGT, $useHouseFilter, function($q) use ($orgHouseIds) {
                    if (!empty($orgHouseIds)) $q->where_in('house_id', $orgHouseIds);
                    else $q->where('1=0', null, false);
                });
                $guestToday = (int)$qbGT->count_all_results();

                $qbGW = $this->db->from('guest_visits')
                    ->where('visit_at >=', $weekStart)
                    ->where('visit_at <=', $todayEnd);
                $this->qb_if($qbGW, $useHouseFilter, function($q) use ($orgHouseIds) {
                    if (!empty($orgHouseIds)) $q->where_in('house_id', $orgHouseIds);
                    else $q->where('1=0', null, false);
                });
                $guestWeek = (int)$qbGW->count_all_results();

                $qbGL = $this->db->select('g.id, g.visitor_name AS guest_name, g.purpose, g.visit_at, g.created_at, hs.code AS house_code')
                    ->from('guest_visits g')
                    ->join('houses hs', 'hs.id = g.house_id', 'left')
                    ->order_by('g.id', 'desc')
                    ->limit(10);
                $this->qb_if($qbGL, $useHouseFilter, function($q) use ($orgHouseIds) {
                    if (!empty($orgHouseIds)) $q->where_in('g.house_id', $orgHouseIds);
                    else $q->where('1=0', null, false);
                });
                $guestLatest = $qbGL->get()->result_array();
            }

            $out['security'] = [
                'emergencies_open' => $emOpen,
                'emergencies_latest' => $emLatest,
                'emergencies_monthly' => array_values($emMonthly),
                'guest_visits_today' => $guestToday,
                'guest_visits_week' => $guestWeek,
                'guest_visits_latest' => $guestLatest,
                'feedback_open' => $fbOpen,
                'feedback_latest' => $fbLatest,
                'feedback_top_categories' => $fbTopCats,
            ];
        }

        if ($this->has_permission('app.home.dashboard.widget.content')) {
            $now = date('Y-m-d H:i:s');
            $eventsUpcoming = $this->db->select('id, title, event_at, location')
                ->from('events')
                ->where('event_at >=', $now)
                ->order_by('event_at', 'asc')
                ->limit(10)
                ->get()->result_array();

            $postsLatest = $this->db->select('id, title, category, created_at')
                ->from('posts')
                ->where('status', 'published')
                ->order_by('id', 'desc')
                ->limit(10)
                ->get()->result_array();

            $pollsActive = $this->db->select('id, title, status, start_at, end_at, vote_scope')
                ->from('polls')
                ->where('status', 'published')
                ->order_by('id', 'desc')
                ->limit(10)
                ->get()->result_array();

            $pollsActiveCount = (int)$this->db->from('polls')->where('status', 'published')->count_all_results();

            $pollParticipation = [];
            $participationRate = 0.0;

            if ($this->db->table_exists('poll_votes') && !empty($pollsActive)) {
                $useHHFilter = is_array($orgHouseholdIds);

                $qbTH = $this->db->from('households');
                $this->qb_if($qbTH, $useHHFilter, function($q) use ($orgHouseholdIds) {
                    if (!empty($orgHouseholdIds)) $q->where_in('id', $orgHouseholdIds);
                    else $q->where('1=0', null, false);
                });
                $totalHouseholdsForPoll = (int)$qbTH->count_all_results();

                $totalUsersForPoll = (int)$this->db->from('users')->where('status', 'active')->count_all_results();

                $sumPct = 0.0;
                $countPct = 0;

                foreach ($pollsActive as $p) {
                    $pollId = (int)($p['id'] ?? 0);
                    if ($pollId <= 0) continue;

                    $scopePoll = (string)($p['vote_scope'] ?? 'household');
                    $col = ($scopePoll === 'household') ? 'household_id' : 'user_id';
                    $total = ($scopePoll === 'household') ? $totalHouseholdsForPoll : $totalUsersForPoll;

                    $votes = (int)($this->db->select("COUNT(DISTINCT {$col}) AS c", false)
                        ->from('poll_votes')
                        ->where('poll_id', $pollId)
                        ->get()->row()->c ?? 0);

                    $pct = $total > 0 ? round(($votes / $total) * 100, 2) : 0.0;

                    $pollParticipation[] = [
                        'poll_id' => $pollId,
                        'title' => (string)($p['title'] ?? 'Polling'),
                        'vote_scope' => $scopePoll,
                        'votes' => $votes,
                        'total' => $total,
                        'percent' => $pct,
                    ];

                    $sumPct += $pct;
                    $countPct++;
                }

                $participationRate = $countPct > 0 ? round($sumPct / $countPct, 2) : 0.0;
            }

            $out['content'] = [
                'events_upcoming' => $eventsUpcoming,
                'events_upcoming_count' => count($eventsUpcoming),
                'posts_latest' => $postsLatest,
                'posts_count' => count($postsLatest),
                'polls_active' => $pollsActive,
                'polls_active_count' => $pollsActiveCount,
                'poll_participation_rate' => $participationRate,
                'poll_participation' => $pollParticipation,
            ];
        }

        if ($this->has_permission('app.home.dashboard.widget.market')) {
            $filterBizByOrg = ($orgUnitId !== '' && $this->db->field_exists('org_unit_id', 'local_businesses'));
            $filterProdByOrg = ($orgUnitId !== '' && $this->db->field_exists('org_unit_id', 'local_products'));

            $qb = $this->db->from('local_businesses')->where('status', 'pending');
            if ($filterBizByOrg) $qb->where('org_unit_id', $orgUnitId);
            $bizPending = (int)$qb->count_all_results();
            $this->db->reset_query();

            $qb = $this->db->from('local_businesses')->where('status', 'active');
            if ($filterBizByOrg) $qb->where('org_unit_id', $orgUnitId);
            $bizActive = (int)$qb->count_all_results();
            $this->db->reset_query();

            $qb = $this->db->from('local_products')->where('status', 'active');
            if ($filterProdByOrg) $qb->where('org_unit_id', $orgUnitId);
            $prodActive = (int)$qb->count_all_results();
            $this->db->reset_query();

            $qb = $this->db->from('local_products');
            if ($filterProdByOrg) $qb->where('org_unit_id', $orgUnitId);
            $prodTotal = (int)$qb->count_all_results();
            $this->db->reset_query();

            $qb = $this->db->select('status, COUNT(*) AS c', false)
                ->from('local_businesses');
            if ($filterBizByOrg) $qb->where('org_unit_id', $orgUnitId);
            $bizByStatus = $qb->group_by('status')->order_by('c', 'desc')->get()->result_array();
            $this->db->reset_query();

            $qb = $this->db->select('id, name, category, status, created_at')
                ->from('local_businesses');
            if ($filterBizByOrg) $qb->where('org_unit_id', $orgUnitId);
            $bizLatest = $qb->order_by('id', 'desc')->limit(10)->get()->result_array();
            $this->db->reset_query();

            $out['market'] = [
                'business_pending' => $bizPending,
                'business_active' => $bizActive,
                'products_active' => $prodActive,
                'products_count' => $prodTotal,
                'businesses_latest' => $bizLatest,
                'businesses_by_status' => $bizByStatus,
            ];
        }

        if ($this->has_permission('app.home.dashboard.widget.inventory')) {
            $filterInvByOrg = ($orgUnitId !== '' && $this->db->field_exists('org_unit_id', 'inventories'));

            $qb = $this->db->from('inventories');
            if ($filterInvByOrg) $qb->where('org_unit_id', $orgUnitId);
            $invTotal = (int)$qb->count_all_results();
            $this->db->reset_query();

            $qb = $this->db->from('inventories')->where('status', 'active');
            if ($filterInvByOrg) $qb->where('org_unit_id', $orgUnitId);
            $invActive = (int)$qb->count_all_results();
            $this->db->reset_query();

            $qb = $this->db->from('inventories')->where('status', 'archived');
            if ($filterInvByOrg) $qb->where('org_unit_id', $orgUnitId);
            $invArchived = (int)$qb->count_all_results();
            $this->db->reset_query();

            $logsLatest = [];
            if ($this->db->table_exists('inventory_logs')) {
                $qb = $this->db->select('l.id, l.inventory_id, i.name AS inventory_name, l.action, l.qty_change AS qty, l.note, l.created_at')
                    ->from('inventory_logs l')
                    ->join('inventories i', 'i.id = l.inventory_id', 'left');
                if ($filterInvByOrg) $qb->where('i.org_unit_id', $orgUnitId);
                $logsLatest = $qb->order_by('l.id', 'desc')->limit(10)->get()->result_array();
                $this->db->reset_query();
            }

            $out['inventory'] = [
                'items_total' => $invTotal,
                'items_active' => $invActive,
                'items_archived' => $invArchived,
                'logs_count' => count($logsLatest),
                'logs_latest' => $logsLatest,
            ];
        }

        if ($this->has_permission('app.home.dashboard.widget.donation')) {
            $filterFundByOrg = ($orgUnitId !== '' && $this->db->field_exists('org_unit_id', 'fundraisers'));

            $qbFundActive = $this->db->from('fundraisers')
                ->where('status', 'active')
                ->where('category', $scope);
            $this->qb_if($qbFundActive, $filterFundByOrg, function($q) use ($orgUnitId) {
                $q->where('org_unit_id', $orgUnitId);
            });
            $fundActive = (int)$qbFundActive->count_all_results();

            $qbDonTotal = $this->db->select('COALESCE(SUM(d.amount),0) AS s', false)
                ->from('fundraiser_donations d')
                ->join('fundraisers f', 'f.id = d.fundraiser_id', 'left')
                ->where('d.status', 'approved')
                ->where('f.category', $scope)
                ->where('d.paid_at >=', $fromTs)
                ->where('d.paid_at <=', $toTs);
            $this->qb_if($qbDonTotal, $filterFundByOrg, function($q) use ($orgUnitId) {
                $q->where('f.org_unit_id', $orgUnitId);
            });
            $donTotal = (float)($qbDonTotal->get()->row()->s ?? 0);

            $qbDonCount = $this->db->from('fundraiser_donations d')
                ->join('fundraisers f', 'f.id = d.fundraiser_id', 'left')
                ->where('f.category', $scope)
                ->where('d.paid_at >=', $fromTs)
                ->where('d.paid_at <=', $toTs);
            $this->qb_if($qbDonCount, $filterFundByOrg, function($q) use ($orgUnitId) {
                $q->where('f.org_unit_id', $orgUnitId);
            });
            $donCount = (int)$qbDonCount->count_all_results();

            $qbDonPending = $this->db->from('fundraiser_donations d')
                ->join('fundraisers f', 'f.id = d.fundraiser_id', 'left')
                ->where('d.status', 'pending')
                ->where('f.category', $scope)
                ->where('d.paid_at >=', $fromTs)
                ->where('d.paid_at <=', $toTs);
            $this->qb_if($qbDonPending, $filterFundByOrg, function($q) use ($orgUnitId) {
                $q->where('f.org_unit_id', $orgUnitId);
            });
            $donPending = (int)$qbDonPending->count_all_results();

            $qbDonLatest = $this->db->select('d.id, d.fundraiser_id, f.title AS fundraiser_title, d.amount, d.paid_at, d.status')
                ->from('fundraiser_donations d')
                ->join('fundraisers f', 'f.id = d.fundraiser_id', 'left')
                ->where('f.category', $scope)
                ->where('d.paid_at >=', $fromTs)
                ->where('d.paid_at <=', $toTs)
                ->order_by('d.paid_at', 'desc')
                ->limit(10);
            $this->qb_if($qbDonLatest, $filterFundByOrg, function($q) use ($orgUnitId) {
                $q->where('f.org_unit_id', $orgUnitId);
            });
            $donLatest = $qbDonLatest->get()->result_array();

            $qbPerFund = $this->db->select('f.id AS fundraiser_id, f.title AS name, COALESCE(SUM(d.amount),0) AS amount', false)
                ->from('fundraisers f')
                ->join('fundraiser_donations d', 'd.fundraiser_id = f.id AND d.status = "approved"', 'left', false)
                ->where('f.status', 'active')
                ->where('f.category', $scope)
                ->group_by('f.id')
                ->order_by('amount', 'desc')
                ->limit(5);
            $this->qb_if($qbPerFund, $filterFundByOrg, function($q) use ($orgUnitId) {
                $q->where('f.org_unit_id', $orgUnitId);
            });
            $perFund = $qbPerFund->get()->result_array();

            $updatesLatest = [];
            if ($this->db->table_exists('fundraiser_updates')) {
                $qbUpd = $this->db->select('u.id, u.fundraiser_id, f.title AS fundraiser_title, u.title, u.created_at')
                    ->from('fundraiser_updates u')
                    ->join('fundraisers f', 'f.id = u.fundraiser_id', 'left')
                    ->where('f.category', $scope)
                    ->order_by('u.id', 'desc')
                    ->limit(10);
                $this->qb_if($qbUpd, $filterFundByOrg, function($q) use ($orgUnitId) {
                    $q->where('f.org_unit_id', $orgUnitId);
                });
                $updatesLatest = $qbUpd->get()->result_array();
            }

            $out['donation'] = [
                'fundraisers_active' => $fundActive,
                'donations_amount' => $donTotal,
                'donations_count' => $donCount,
                'donations_pending' => $donPending,
                'donations_latest' => $donLatest,
                'donations_per_fundraiser' => $perFund,
                'by_fundraiser' => $perFund,
                'updates_latest' => $updatesLatest,
            ];
        }

        api_ok($out);
    }

    private function month_periods(string $fromDate, string $toDate): array
    {
        $out = [];
        $start = new DateTime($fromDate);
        $end = new DateTime($toDate);
        $start->modify('first day of this month');
        $end->modify('first day of this month');
        while ($start <= $end) {
            $out[] = $start->format('Y-m');
            $start->modify('+1 month');
        }
        return $out;
    }

    private function billing_aging_all(?array $householdIds = null): array
    {
        $qb = $this->db->select("
            CASE
            WHEN DATEDIFF(NOW(), created_at) <= 30 THEN '0–30 hari'
            WHEN DATEDIFF(NOW(), created_at) <= 90 THEN '31–90 hari'
            ELSE '> 90 hari'
            END AS bucket,
            COUNT(*) AS c,
            COALESCE(SUM(total_amount),0) AS amount
        ", false)
        ->from('invoices')
        ->where_in('status', ['unpaid', 'partial'])
        ->group_by('bucket');

        $this->qb_if($qb, is_array($householdIds), function($q) use ($householdIds) {
            if (!empty($householdIds)) $q->where_in('household_id', $householdIds);
            else $q->where('1=0', null, false);
        });

        $rows = $qb->get()->result_array();

        $b = [
            '0–30 hari' => ['bucket' => '0–30 hari', 'count' => 0, 'amount' => 0.0],
            '31–90 hari' => ['bucket' => '31–90 hari', 'count' => 0, 'amount' => 0.0],
            '> 90 hari' => ['bucket' => '> 90 hari', 'count' => 0, 'amount' => 0.0],
        ];

        foreach ($rows as $r) {
            $k = (string)($r['bucket'] ?? '');
            if ($k === '' || !isset($b[$k])) continue;
            $b[$k]['count'] = (int)($r['c'] ?? 0);
            $b[$k]['amount'] = (float)($r['amount'] ?? 0);
        }

        return array_values($b);
    }
}
