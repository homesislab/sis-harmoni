<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_model extends MY_Model
{
    protected string $table_name = 'inventories';

    private string $table = 'inventories';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'code' => trim((string)$data['code']),
            'name' => trim((string)$data['name']),
            'category' => $data['category'] ?? null,
            'location_text' => $data['location_text'] ?? null,
            'condition' => $data['condition'] ?? 'good',
            'qty' => isset($data['qty']) ? (int)$data['qty'] : 1,
            'unit' => $data['unit'] ?? null,
            'acquired_at' => $data['acquired_at'] ?? null,
            'purchase_price' => array_key_exists('purchase_price', $data) ? $data['purchase_price'] : null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['code','name','category','location_text','condition','qty','unit','acquired_at','purchase_price','notes','status'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $upd[$k] = $data[$k];
            }
        }
        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update($this->table, $upd);
        }
    }

    public function delete(int $id): void
    {
        $this->db->where('id', $id)->delete($this->table);
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;
        $qb = $this->db->from($this->table);

        if (!empty($filters['status'])) {
            $qb->where('status', (string)$filters['status']);
        }
        if (!empty($filters['category'])) {
            $qb->where('category', (string)$filters['category']);
        }
        if (!empty($filters['condition'])) {
            $qb->where('condition', (string)$filters['condition']);
        }
        if (!empty($filters['q'])) {
            $q = (string)$filters['q'];
            $qb->group_start()
                ->like('code', $q)
                ->or_like('name', $q)
                ->or_like('category', $q)
                ->or_like('location_text', $q)
            ->group_end();
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
}
