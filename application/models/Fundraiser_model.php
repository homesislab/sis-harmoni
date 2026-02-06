<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fundraiser_model extends CI_Model
{
    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];
        if ($is_create) {
            foreach (['title','category'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') $err[$f] = 'Wajib diisi';
            }
        }

        if (isset($in['category'])) {
            $c = trim((string)$in['category']);
            if (!in_array($c, ['paguyuban','dkm'], true)) $err['category'] = 'Nilai tidak valid';
        }

        if (isset($in['status'])) {
            $s = trim((string)$in['status']);
            if (!in_array($s, ['active','closed'], true)) $err['status'] = 'Nilai tidak valid';
        }

        if (isset($in['target_amount']) && (float)$in['target_amount'] < 0) $err['target_amount'] = 'Tidak boleh negatif';

        return $err;
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('fundraisers', ['id'=>$id])->row_array();
        return $row ?: null;
    }

    public function create(array $in): int
    {
        $this->db->insert('fundraisers', [
            'title' => trim((string)$in['title']),
            'description' => isset($in['description']) ? (string)$in['description'] : null,
            'target_amount' => isset($in['target_amount']) ? (float)$in['target_amount'] : 0,
            'collected_amount' => 0,
            'status' => isset($in['status']) ? trim((string)$in['status']) : 'active',
            'category' => trim((string)$in['category']),
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $in): void
    {
        $allowed = ['title','description','target_amount','status','category'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $in)) {
                $upd[$k] = is_string($in[$k]) ? trim((string)$in[$k]) : $in[$k];
            }
        }
        if ($upd) $this->db->where('id',$id)->update('fundraisers', $upd);
    }

    public function delete(int $id): void
    {
        $this->db->where('id',$id)->delete('fundraisers');
    }

    public function set_status(int $id, string $status): void
    {
        $this->db->where('id',$id)->update('fundraisers', ['status'=>$status]);
    }

    public function add_collected(int $id, float $amount): void
    {
        $this->db->set('collected_amount', 'collected_amount + ' . (float)$amount, false)
                 ->where('id', $id)
                 ->update('fundraisers');
    }

    public function paginate(int $page, int $per, array $filters): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('fundraisers f');

        if (!empty($filters['category'])) $qb->where('f.category', (string)$filters['category']);
        if (!empty($filters['status'])) $qb->where('f.status', (string)$filters['status']);

        if (!empty($filters['q'])) {
            $q = (string)$filters['q'];
            $qb->group_start()
               ->like('f.title', $q)
               ->or_like('f.description', $q)
               ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('f.*')
            ->order_by('f.created_at','DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page'=>$page,'per_page'=>$per,'total'=>$total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
