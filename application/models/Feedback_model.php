<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Feedback_model extends MY_Model
{
    protected string $table_name = 'feedbacks';

    private string $table = 'feedbacks';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'category_id' => isset($data['category_id']) ? (int)$data['category_id'] : null,
            'person_id' => isset($data['person_id']) ? (int)$data['person_id'] : null,
            'house_id' => isset($data['house_id']) ? (int)$data['house_id'] : null,
            'title' => trim((string)$data['title']),
            'message' => (string)$data['message'],
            'status' => $data['status'] ?? 'open',
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['status','assigned_to','closed_by','closed_at','category_id','title','message'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $upd[$k] = $data[$k];
            }
        }
        if ($upd) {
            $this->db->where('id', $id)->update($this->table, $upd);
        }
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db
            ->select('f.*, c.name AS category_name, p.full_name AS person_name, h.code AS house_code')
            ->from($this->table . ' f')
            ->join('feedback_categories c', 'c.id = f.category_id', 'left')
            ->join('persons p', 'p.id = f.person_id', 'left')
            ->join('houses h', 'h.id = f.house_id', 'left');

        if (!empty($filters['status'])) {
            $qb->where('f.status', (string)$filters['status']);
        }
        if (!empty($filters['created_by'])) {
            $qb->where('f.created_by', (int)$filters['created_by']);
        }
        if (!empty($filters['person_id'])) {
            $qb->where('f.person_id', (int)$filters['person_id']);
        }

        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);
            if ($q !== '') {
                $qb->group_start()
                    ->like('f.title', $q)
                    ->or_like('f.message', $q)
                    ->or_like('p.full_name', $q)
                    ->or_like('h.code', $q)
                    ->or_like('f.id', $q)
                ->group_end();
            }
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->order_by('f.id', 'DESC')->limit($per, $offset)->get()->result_array();
        $total_pages = ($per > 0 ? (int)ceil($total / $per) : 0);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,'per_page' => $per,'total' => $total,'total_pages' => $total_pages,
                'has_prev' => $page > 1,'has_next' => $page < $total_pages
            ]
        ];
    }
}
