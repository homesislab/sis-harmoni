<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Donation_model extends CI_Model
{
    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('d.*, p.full_name, f.title AS fundraiser_title, f.category AS fundraiser_category')
            ->from('fundraiser_donations d')
            ->join('persons p', 'p.id = d.person_id', 'left')
            ->join('fundraisers f', 'f.id = d.fundraiser_id', 'left')
            ->where('d.id', $id)
            ->get()->row_array();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert('fundraiser_donations', [
            'fundraiser_id' => (int)$data['fundraiser_id'],
            'person_id' => (int)$data['person_id'],
            'amount' => (float)$data['amount'],
            'paid_at' => $data['paid_at'],
            'proof_file_url' => $data['proof_file_url'] ?? null,
            'note' => $data['note'] ?? null,
            'is_anonymous' => !empty($data['is_anonymous']) ? 1 : 0,
            'status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
        ]);
        return (int)$this->db->insert_id();
    }

    public function approve(int $id, int $verified_by): void
    {
        $this->db->where('id', $id)->update('fundraiser_donations', [
            'status' => 'approved',
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function reject(int $id, int $verified_by, ?string $note = null): void
    {
        $this->db->where('id', $id)->update('fundraiser_donations', [
            'status' => 'rejected',
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
            'note' => $note,
        ]);
    }

    public function paginate_admin(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db
            ->select('d.*, f.title as fundraiser_title, p.full_name')
            ->from('fundraiser_donations d')
            ->join('fundraisers f', 'f.id=d.fundraiser_id', 'left')
            ->join('persons p', 'p.id=d.person_id', 'left');

        if (!empty($filters['status'])) {
            $qb->where('d.status', (string)$filters['status']);
        }

        if (!empty($filters['q'])) {
            $kw = trim((string)$filters['q']);
            if ($kw !== '') {
                $qb->group_start()
                    ->like('f.title', $kw)
                    ->or_like('p.full_name', $kw)
                    ->or_like('d.note', $kw)
                    ->or_like('d.amount', $kw)
                ->group_end();
            }
        }

        $totalQ = clone $qb;
        $total  = (int)$totalQ->count_all_results('', false);

        $items = $qb
            ->order_by('d.id', 'desc')
            ->limit($per, $offset)
            ->get()
            ->result_array();

        return [
            'items' => $items,
            'meta'  => api_pagination_meta($page, $per, $total),
        ];
    }

    public function paginate_for_fundraiser(int $fundraiser_id, ?string $status, int $page, int $per): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('fundraiser_donations d')
            ->join('persons p', 'p.id = d.person_id', 'left')
            ->where('d.fundraiser_id', $fundraiser_id);

        if ($status) {
            $qb->where('d.status', $status);
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('d.id, d.fundraiser_id, d.person_id, d.amount, d.paid_at, d.proof_file_url, d.note, d.is_anonymous, d.status, d.verified_by, d.verified_at, d.created_at, p.full_name')
            ->order_by('d.paid_at', 'DESC')
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

    public function count_approved_for_fundraiser(int $fundraiser_id): int
    {
        return (int)$this->db->from('fundraiser_donations')
            ->where('fundraiser_id', $fundraiser_id)
            ->where('status', 'approved')
            ->count_all_results();
    }
}
