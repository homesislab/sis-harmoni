<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Important_contact_model extends CI_Model
{
    private string $table = 'important_contacts';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id'=>$id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'name' => trim((string)$data['name']),
            'category' => $data['category'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'description' => $data['description'] ?? null,
            'is_public' => isset($data['is_public']) ? (int)$data['is_public'] : 1,
            'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['name','category','phone','whatsapp','description','is_public','sort_order'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k,$data)) $upd[$k] = $data[$k];
        }
        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id',$id)->update($this->table,$upd);
        }
    }

    public function delete(int $id): void
    {
        $this->db->where('id',$id)->delete($this->table);
    }

    private function apply_filters(array $filters = []): void
    {
        if (array_key_exists('is_public', $filters) && $filters['is_public'] !== null) {
            $this->db->where('is_public', (int)$filters['is_public']);
        }

        if (!empty($filters['category'])) {
            $this->db->where('category', (string)$filters['category']);
        }

        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);
            if ($q !== '') {
                $this->db->group_start()
                    ->like('name', $q)
                    ->or_like('category', $q)
                    ->or_like('phone', $q)
                    ->or_like('whatsapp', $q)
                    ->or_like('description', $q)
                ->group_end();
            }
        }
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $page = max(1, (int)$page);
        $per = min(100, max(1, (int)$per));
        $offset = ($page - 1) * $per;

        $qb = $this->db->from($this->table);
        $this->apply_filters($filters);

        $total = (int)$qb->count_all_results('', false);

        $items = $qb
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
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
