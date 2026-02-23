<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Household_model extends MY_Model
{
    protected string $table_name = 'households';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('households', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function find_by_kk(string $kk_number): ?array
    {
        $row = $this->db->get_where('households', ['kk_number' => $kk_number])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert('households', [
            'kk_number' => $data['kk_number'],
            'head_person_id' => (int)$data['head_person_id'],
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $upd = [];
        if (isset($data['kk_number'])) {
            $upd['kk_number'] = $data['kk_number'];
        }
        if (isset($data['head_person_id'])) {
            $upd['head_person_id'] = (int)$data['head_person_id'];
        }
        if ($upd) {
            $this->db->where('id', $id)->update('households', $upd);
        }
    }

    public function add_member(int $household_id, int $person_id, string $relationship): bool
    {
        $exists = $this->db->get_where('household_members', [
            'household_id' => $household_id,
            'person_id' => $person_id,
        ])->row_array();

        if ($exists) {
            return false;
        }

        $this->db->insert('household_members', [
            'household_id' => $household_id,
            'person_id' => $person_id,
            'relationship' => $relationship,
        ]);
        return true;
    }

    public function update_member_relationship(int $household_member_id, string $relationship): ?array
    {
        $row = $this->db->get_where('household_members', ['id' => $household_member_id])->row_array();
        if (!$row) {
            return null;
        }

        $this->db->where('id', $household_member_id)->update('household_members', [
            'relationship' => $relationship,
        ]);

        $m = $this->db->select('hm.id AS household_member_id, hm.relationship, p.*')
            ->from('household_members hm')
            ->join('persons p', 'p.id = hm.person_id', 'inner')
            ->where('hm.id', $household_member_id)
            ->get()->row_array();

        return $m ?: null;
    }

    public function remove_member(int $household_member_id): bool
    {
        $row = $this->db->get_where('household_members', ['id' => $household_member_id])->row_array();
        if (!$row) {
            return false;
        }
        $this->db->where('id', $household_member_id)->delete('household_members');
        return true;
    }

    public function person_is_member(int $person_id, int $household_id): bool
    {
        $row = $this->db->get_where('household_members', [
            'household_id' => $household_id,
            'person_id' => $person_id,
        ])->row_array();
        return (bool)$row;
    }

    public function find_detail(int $id): ?array
    {
        $hh = $this->find_by_id($id);
        if (!$hh) {
            return null;
        }

        $head = $this->db->get_where('persons', ['id' => (int)$hh['head_person_id']])->row_array();

        $members = $this->db->select('hm.id AS household_member_id, hm.relationship, p.*')
            ->from('household_members hm')
            ->join('persons p', 'p.id = hm.person_id', 'inner')
            ->where('hm.household_id', $id)
            ->order_by('hm.id', 'ASC')
            ->get()->result_array();

        return [
            'household' => $hh,
            'head' => $head ?: null,
            'members' => $members,
        ];
    }

    public function paginate(int $page, int $per, string $q = ''): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('households h')
            ->join('persons p', 'p.id = h.head_person_id', 'left');

        if ($q !== '') {
            $qb->group_start()
               ->like('h.kk_number', $q)
               ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('h.*, p.full_name AS head_person_name')
                    ->order_by('h.id', 'DESC')
                    ->limit($per, $offset)
                    ->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page' => $page,'per_page' => $per,'total' => $total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }

    public function paginate_for_person(int $person_id, int $page, int $per, string $q = ''): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('households h')
            ->join('household_members hm', 'hm.household_id = h.id', 'inner')
            ->join('persons p', 'p.id = h.head_person_id', 'left')
            ->where('hm.person_id', $person_id);

        if ($q !== '') {
            $qb->like('h.kk_number', $q);
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('h.*, p.full_name AS head_person_name')
            ->order_by('h.id', 'DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page' => $page,'per_page' => $per,'total' => $total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
