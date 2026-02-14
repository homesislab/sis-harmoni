<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Local_business_model extends CI_Model
{
    private string $table = 'local_businesses';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db
            ->select('b.*, h.code AS house_code, h.block AS house_block, h.number AS house_number, h.type AS house_type')
            ->from($this->table . ' b')
            ->join('houses h', 'h.id = b.house_id', 'left')
            ->where('b.id', $id)
            ->get()
            ->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'owner_person_id' => isset($data['owner_person_id']) ? (int)$data['owner_person_id'] : null,
            'house_id' => isset($data['house_id']) ? (int)$data['house_id'] : null,
            'name' => trim((string)$data['name']),
            'category' => trim((string)$data['category']),
            'description' => $data['description'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_lapak' => isset($data['is_lapak']) ? (int)$data['is_lapak'] : 0,
            'status' => $data['status'] ?? 'pending',
            'verification_note' => $data['verification_note'] ?? null,
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = [
            'name','category','description','whatsapp','phone','address',
            'is_lapak','status','verification_note','approved_by','approved_at'
        ];

        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) $upd[$k] = $data[$k];
        }

        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update($this->table, $upd);
        }
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db
            ->from($this->table . ' b')
            ->join('houses h', 'h.id = b.house_id', 'left');

        if (array_key_exists('owner_person_id', $filters) && $filters['owner_person_id'] !== null) {
            $qb->where('b.owner_person_id', (int)$filters['owner_person_id']);
        }

        if (!empty($filters['status'])) {
            $qb->where('b.status', (string)$filters['status']);
        }

        if (array_key_exists('is_lapak', $filters) && $filters['is_lapak'] !== null) {
            $qb->where('b.is_lapak', (int)$filters['is_lapak']);
        }

        if (!empty($filters['category'])) {
            $qb->where('b.category', (string)$filters['category']);
        }

        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);
            if ($q !== '') {
                $qb->group_start()
                    ->like('b.name', $q)
                    ->or_like('b.description', $q)
                    ->or_like('b.category', $q)
                    ->or_like('b.whatsapp', $q)
                    ->or_like('b.phone', $q)
                    ->or_like('b.address', $q)
                    ->or_like('h.code', $q)
                    ->or_like('h.block', $q)
                    ->or_like('h.number', $q)
                ->group_end();
            }
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb
            ->select('b.*, h.code AS house_code, h.block AS house_block, h.number AS house_number, h.type AS house_type')
            ->order_by('b.id', 'DESC')
            ->limit($per, $offset)
            ->get()
            ->result_array();

        $total_pages = ($per > 0 ? (int)ceil($total / $per) : 0);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_prev' => $page > 1,
                'has_next' => $page < $total_pages,
            ],
        ];
    }
}
