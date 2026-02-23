<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Ledger_model extends MY_Model
{
    protected string $table_name = 'ledger_accounts';

    public function find_account(int $id): ?array
    {
        $row = $this->db
            ->from('ledger_accounts')
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->get()->row_array();
        return $row ?: null;
    }

    public function account_stats(int $ledger_account_id): array
    {
        $acc = $this->find_account($ledger_account_id);
        $balance = (float)($acc['balance'] ?? 0);

        $tot = $this->db->select(
            "SUM(CASE WHEN direction='in' THEN amount ELSE 0 END) AS total_in, " .
                "SUM(CASE WHEN direction='out' THEN amount ELSE 0 END) AS total_out, " .
                "MAX(occurred_at) AS last_entry_at",
            false
        )
            ->from('ledger_entries')
            ->where('ledger_account_id', $ledger_account_id)
            ->get()->row_array();

        return [
            'balance' => $balance,
            'total_in' => (float)($tot['total_in'] ?? 0),
            'total_out' => (float)($tot['total_out'] ?? 0),
            'last_entry_at' => $tot['last_entry_at'] ?? null,
        ];
    }

    public function ensure_default_account(string $type): int
    {
        $row = $this->db->from('ledger_accounts')->where('type', $type)->order_by('id', 'ASC')->get()->row_array();
        if ($row) {
            return (int)$row['id'];
        }

        $this->db->insert('ledger_accounts', [
            'name' => strtoupper($type) . ' - Main',
            'type' => $type,
            'balance' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->insert_id();
    }

    public function list_accounts(?string $type = null): array
    {
        $qb = $this->db->from('ledger_accounts')
            ->where('deleted_at IS NULL', null, false);

        if ($type !== null) {
            $qb->where('type', $type);
        }

        return $qb->order_by('id', 'ASC')->get()->result_array();
    }

    public function create_account(array $in): int
    {
        $this->db->insert('ledger_accounts', [
            'name' => $in['name'],
            'type' => $in['type'],
            'balance' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $in['created_by'] ?? null,
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_account(int $id, array $in): void
    {
        $this->db->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update('ledger_accounts', [
                'name' => $in['name'],
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $in['updated_by'] ?? null,
            ]);
    }

    public function soft_delete_account(int $id, int $by = 0): void
    {
        $this->db->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update('ledger_accounts', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $by ?: null,
            ]);
    }

    public function has_entries(int $ledger_account_id): bool
    {
        $row = $this->db->select('id')
            ->from('ledger_entries')
            ->where('ledger_account_id', $ledger_account_id)
            ->limit(1)->get()->row_array();
        return !empty($row);
    }

    public function paginate_entries(int $page, int $per, array $filters = []): array
    {
        $page = max(1, $page);
        $per  = max(1, min(100, $per));
        $offset = ($page - 1) * $per;

        $qb = $this->db->select('e.*, a.name AS account_name, a.type AS account_type')
            ->from('ledger_entries e')
            ->join('ledger_accounts a', 'a.id = e.ledger_account_id', 'left');

        if (!empty($filters['ledger_account_id'])) {
            $qb->where('e.ledger_account_id', (int)$filters['ledger_account_id']);
        }
        if (!empty($filters['direction']) && in_array($filters['direction'], ['in','out'], true)) {
            $qb->where('e.direction', $filters['direction']);
        }
        if (!empty($filters['from'])) {
            $qb->where('e.occurred_at >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $qb->where('e.occurred_at <=', $filters['to']);
        }

        $countQ = clone $qb;
        $total = (int)$countQ->count_all_results('', false);

        $items = $qb->order_by('e.occurred_at', 'DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }

    public function create_entry(array $in): int
    {
        $this->db->insert('ledger_entries', [
            'ledger_account_id' => (int)$in['ledger_account_id'],
            'direction' => $in['direction'],
            'amount' => (float)$in['amount'],
            'category' => $in['category'] ?? null,
            'description' => $in['description'] ?? null,
            'occurred_at' => $in['occurred_at'] ?? date('Y-m-d H:i:s'),
            'source_type' => $in['source_type'] ?? null,
            'source_id' => $in['source_id'] ?? null,
            'created_by' => $in['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int)$this->db->insert_id();

        $amount = (float)$in['amount'];
        if ($in['direction'] === 'in') {
            $this->db->set('balance', 'balance + ' . $this->db->escape($amount), false)
                ->where('id', (int)$in['ledger_account_id'])
                ->update('ledger_accounts');
        } else {
            $this->db->set('balance', 'balance - ' . $this->db->escape($amount), false)
                ->where('id', (int)$in['ledger_account_id'])
                ->update('ledger_accounts');
        }

        return $id;
    }
}
