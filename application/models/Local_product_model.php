<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Local_product_model extends CI_Model
{
    private string $table = 'local_products';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id'=>$id])->row_array();
        return $row ?: null;
    }

    public function list_by_business(int $business_id, ?string $status = 'active'): array
    {
        $qb = $this->db->from($this->table)->where('business_id', $business_id)->order_by('id','DESC');
        if ($status) $qb->where('status', $status);
        return $qb->get()->result_array();
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from($this->table);

        if (!empty($filters['business_id'])) {
            $qb->where('business_id', (int)$filters['business_id']);
        }

        if (!empty($filters['status'])) {
            $qb->where('status', (string)$filters['status']);
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb
            ->order_by('id', 'DESC')
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

    public function create(array $data): int
    {
        $payload = [
            'business_id' => (int)$data['business_id'],
            'name' => trim((string)$data['name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? null,
            'unit' => $data['unit'] ?? null,
            'image_url' => $data['image_url'] ?? null,
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
        $allowed = ['name','description','price','unit','image_url','status'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k,$data)) $upd[$k] = $data[$k];
        }
        if ($upd) $this->db->where('id',$id)->update($this->table,$upd);
    }

    public function delete(int $id): void
    {
        $this->db->where('id',$id)->delete($this->table);
    }
}
