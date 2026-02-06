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

        api_ok([
            'unpaid_invoices' => $inv_unpaid_count,
            'unpaid_amount'   => $inv_unpaid_amount,
            'polls_open'      => $polls_open,
            'events_upcoming' => $events_upcoming,
            'posts_latest'    => $posts_latest,
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
            ->group_by(['a.type','e.direction'])
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
}
