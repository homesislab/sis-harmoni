<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Security_guard_model extends MY_Model
{
    private $table = 'security_guards';

    public function get_list(int $page = 1, int $per_page = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $per_page;

        $qb = $this->db->from("{$this->table} sg");

        if ($search !== '') {
            $qb->group_start()
                ->like('sg.full_name', $search)
                ->or_like('sg.employee_id', $search)
                ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('sg.*')
            ->order_by('sg.id', 'DESC')
            ->limit($per_page, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('sg.*')
            ->from("{$this->table} sg")
            ->where('sg.id', $id)
            ->get()->row_array();

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

    public function is_employee_id_taken(string $employee_id, ?int $exclude_id = null): bool
    {
        $this->db->where('employee_id', $employee_id);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        $count = $this->db->count_all_results($this->table);
        return $count > 0;
    }
}
