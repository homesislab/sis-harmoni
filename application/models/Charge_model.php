<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Charge_model extends MY_Model
{
    protected string $table_name = 'charge_types';

    public function validate_type(array $in, bool $is_create): array
    {
        $err = [];

        if ($is_create) {
            foreach (['name','category'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                    $err[$f] = 'Wajib diisi';
                }
            }
        }

        if (isset($in['category'])) {
            $c = trim((string)$in['category']);
            if (!in_array($c, ['paguyuban','dkm'], true)) {
                $err['category'] = 'Nilai tidak valid';
            }
        }

        if (isset($in['period_unit'])) {
            $p = trim((string)$in['period_unit']);
            if (!in_array($p, ['monthly','weekly','once'], true)) {
                $err['period_unit'] = 'Nilai tidak valid';
            }
        }

        if (isset($in['is_periodic']) && !in_array((int)$in['is_periodic'], [0,1], true)) {
            $err['is_periodic'] = 'Nilai tidak valid';
        }

        if (isset($in['is_active']) && !in_array((int)$in['is_active'], [0,1], true)) {
            $err['is_active'] = 'Nilai tidak valid';
        }

        return $err;
    }

    public function list_types(?string $category = null, $active = null): array
    {
        $qb = $this->db->select("
            ct.*,
            (
            SELECT COUNT(*) FROM charge_components cc
            WHERE cc.charge_type_id = ct.id AND cc.deleted_at IS NULL
            ) AS components_count,
            (
            SELECT COALESCE(SUM(cc.amount),0) FROM charge_components cc
            WHERE cc.charge_type_id = ct.id AND cc.deleted_at IS NULL
            ) AS components_total
        ", false)->from('charge_types ct');

        if ($category) {
            $qb->where('ct.category', $category);
        }
        if ($active !== null && $active !== '') {
            $qb->where('ct.is_active', (int)$active);
        }

        return $qb->order_by('ct.id', 'DESC')->get()->result_array();
    }

    public function find_type(int $id): ?array
    {
        $row = $this->db->get_where('charge_types', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create_type(array $in): int
    {
        $this->db->insert('charge_types', [
            'name' => trim((string)$in['name']),
            'category' => trim((string)$in['category']),
            'is_periodic' => isset($in['is_periodic']) ? (int)$in['is_periodic'] : 1,
            'period_unit' => isset($in['period_unit']) ? trim((string)$in['period_unit']) : 'monthly',
            'is_active' => isset($in['is_active']) ? (int)$in['is_active'] : 1,
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_type(int $id, array $in): void
    {
        $allowed = ['name','category','is_periodic','period_unit','is_active'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $in)) {
                $upd[$k] = is_string($in[$k]) ? trim((string)$in[$k]) : (int)$in[$k];
            }
        }
        if ($upd) {
            $this->db->where('id', $id)->update('charge_types', $upd);
        }
    }

    public function validate_component(array $in, bool $is_create): array
    {
        $err = [];
        if ($is_create) {
            foreach (['charge_type_id','name','amount','ledger_account_id'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                    $err[$f] = 'Wajib diisi';
                }
            }
        }

        if (isset($in['amount']) && (float)$in['amount'] < 0) {
            $err['amount'] = 'Tidak boleh negatif';
        }

        if (isset($in['ledger_account_id'])) {
            $lid = (int)$in['ledger_account_id'];
            if ($lid <= 0) {
                $err['ledger_account_id'] = 'Wajib dipilih';
            }
        }

        return $err;
    }

    public function next_component_sort_order(int $charge_type_id): int
    {
        $row = $this->db->select('COALESCE(MAX(sort_order),0) AS mx', false)
            ->from('charge_components')
            ->where('charge_type_id', $charge_type_id)
            ->where('deleted_at IS NULL', null, false)
            ->get()->row_array();

        return ((int)($row['mx'] ?? 0)) + 1;
    }

    public function list_components(int $charge_type_id): array
    {
        return $this->db->from('charge_components')
            ->where('charge_type_id', $charge_type_id)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()->result_array();
    }

    public function list_components_by_charge_type(int $charge_type_id): array
    {
        return $this->db->from('charge_components')
            ->where('charge_type_id', $charge_type_id)
            ->order_by('id', 'ASC')
            ->get()->result_array();
    }

    public function find_component(int $id): ?array
    {
        $row = $this->db->get_where('charge_components', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create_component(array $in): int
    {
        $charge_type_id = (int)$in['charge_type_id'];

        $this->db->insert('charge_components', [
            'charge_type_id' => $charge_type_id,
            'name' => trim((string)$in['name']),
            'amount' => (float)$in['amount'],
            'sort_order' => $this->next_component_sort_order($charge_type_id), // ✅ auto
            'ledger_account_id' => (int)$in['ledger_account_id'], // ✅ wajib
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_component(int $id, array $in): void
    {
        $allowed = ['name','amount','ledger_account_id']; // ✅ sort_order dihapus
        $upd = [];

        foreach ($allowed as $k) {
            if (!array_key_exists($k, $in)) {
                continue;
            }
            if ($k === 'name') {
                $upd[$k] = trim((string)$in[$k]);
            } elseif ($k === 'amount') {
                $upd[$k] = (float)$in[$k];
            } elseif ($k === 'ledger_account_id') {
                $upd[$k] = (int)$in[$k];
            }
        }

        if ($upd) {
            $this->db->where('id', $id)->update('charge_components', $upd);
        }
    }

    public function delete_component(int $id): void
    {
        $this->db->where('id', $id)->update('charge_components', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function sum_components(int $charge_type_id): float
    {
        $row = $this->db->select('SUM(amount) AS total')
            ->from('charge_components')
            ->where('charge_type_id', $charge_type_id)
            ->get()->row_array();
        return (float)($row['total'] ?? 0);
    }

    public function reorder_components(int $charge_type_id, array $ordered_ids): bool
    {
        $rows = $this->db->select('id')
            ->from('charge_components')
            ->where('charge_type_id', $charge_type_id)
            ->where('deleted_at IS NULL', null, false)
            ->where_in('id', $ordered_ids)
            ->get()->result_array();

        if (count($rows) !== count($ordered_ids)) {
            return false; // ada id yang tidak cocok / sudah deleted
        }

        $this->db->trans_start();
        $order = 1;
        foreach ($ordered_ids as $cid) {
            $this->db->where('id', (int)$cid)
                ->where('charge_type_id', $charge_type_id)
                ->update('charge_components', ['sort_order' => $order]);
            $order++;
        }
        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
