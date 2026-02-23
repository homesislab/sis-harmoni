<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Occupancy_model extends MY_Model
{
    protected string $table_name = 'house_occupancies';

    private string $table = 'house_occupancies';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'house_id'       => (int)$data['house_id'],
            'household_id'   => isset($data['household_id']) ? (int)$data['household_id'] : null,
            'occupancy_type' => (string)$data['occupancy_type'],
            'start_date'     => (string)$data['start_date'],
            'end_date'       => $data['end_date'] ?? null,
            'status'         => $data['status'] ?? 'active',
            'note'           => $data['note'] ?? null,
            'created_at'     => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function end_active_by_house(int $house_id): void
    {
        $this->db->where('house_id', $house_id)
            ->where('status', 'active')
            ->update($this->table, [
                'status' => 'ended',
                'end_date' => date('Y-m-d'),
            ]);
    }

    public function end_active_live_by_house(int $house_id): void
    {
        $this->db->where('house_id', $house_id)
            ->where('status', 'active')
            ->where_in('occupancy_type', ['owner_live','tenant','family','caretaker'])
            ->update($this->table, [
                'status' => 'ended',
                'end_date' => date('Y-m-d'),
            ]);
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['household_id','occupancy_type','start_date','end_date','status','note'];
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

        $qb = $this->db->from($this->table);
        if (!empty($filters['house_id'])) {
            $qb->where('house_id', (int)$filters['house_id']);
        }
        if (!empty($filters['household_id'])) {
            $qb->where('household_id', (int)$filters['household_id']);
        }
        if (!empty($filters['status'])) {
            $qb->where('status', (string)$filters['status']);
        }
        if (!empty($filters['occupancy_type'])) {
            $qb->where('occupancy_type', (string)$filters['occupancy_type']);
        }

        $total = (int)$qb->count_all_results('', false);
        $items = $qb->order_by('id', 'DESC')->limit($per, $offset)->get()->result_array();

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

    public function paginate_for_household(int $household_id, int $page, int $per, array $filters = []): array
    {
        $filters['household_id'] = (int)$household_id;
        return $this->paginate($page, $per, $filters);
    }
}
