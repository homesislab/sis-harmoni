<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ownership_model extends CI_Model
{
    public function end_active_by_house(int $house_id): void
    {
        $this->db->where('house_id',$house_id)
            ->where('end_date IS NULL', null, false)
            ->update('house_ownerships',[ 'end_date' => date('Y-m-d') ]);
    }
    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('house_ownerships', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert('house_ownerships', [
            'house_id' => (int)$data['house_id'],
            'person_id' => (int)$data['person_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
        return (int)$this->db->insert_id();
    }

    public function paginate(int $page, int $per): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('house_ownerships');
        $total = (int)$qb->count_all_results('', false);

        $items = $qb->order_by('id','DESC')->limit($per,$offset)->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page'=>$page,'per_page'=>$per,'total'=>$total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }

    public function paginate_for_person(int $person_id, int $page, int $per): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('house_ownerships')->where('person_id', $person_id);
        $total = (int)$qb->count_all_results('', false);

        $items = $qb->order_by('id','DESC')->limit($per,$offset)->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page'=>$page,'per_page'=>$per,'total'=>$total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
