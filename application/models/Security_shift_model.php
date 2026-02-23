<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Security_shift_model extends MY_Model
{
    private $table = 'security_shifts';

    public function get_list(int $page = 1, int $per_page = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $per_page;

        $qb = $this->db->from($this->table);

        if ($search !== '') {
            $qb->like('name', $search);
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('*')
            ->order_by('start_time', 'ASC')
            ->limit($per_page, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete(int $id): bool
    {
        return $this->db->where('id', $id)->delete($this->table);
    }
}
