<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meeting_minute_model extends CI_Model
{
    private string $table = 'meeting_minutes';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id'=>$id])->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'title' => trim((string)$data['title']),
            'meeting_at' => (string)$data['meeting_at'],
            'location_text' => $data['location_text'] ?? null,
            'agenda' => $data['agenda'] ?? null,
            'summary' => $data['summary'] ?? null,
            'decisions' => $data['decisions'] ?? null,
            'followups' => array_key_exists('followups',$data) ? json_encode($data['followups'], JSON_UNESCAPED_UNICODE) : null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['title','meeting_at','location_text','agenda','summary','decisions','followups','status'];
        $upd = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k,$data)) continue;
            if ($k === 'followups') {
                $upd[$k] = $data[$k] === null ? null : json_encode($data[$k], JSON_UNESCAPED_UNICODE);
            } else {
                $upd[$k] = $data[$k];
            }
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

    public function paginate(int $page, int $per, array $filters=[]): array
    {
        $offset = ($page-1)*$per;
        $qb = $this->db->from($this->table);

        if (!empty($filters['status'])) $qb->where('status',(string)$filters['status']);
        if (!empty($filters['q'])) {
            $q = (string)$filters['q'];
            $qb->group_start()->like('title',$q)->or_like('summary',$q)->or_like('agenda',$q)->group_end();
        }

        $total = (int)$qb->count_all_results('', false);
        $items = $qb->order_by('meeting_at','DESC')->order_by('id','DESC')->limit($per,$offset)->get()->result_array();
        $total_pages = ($per>0?(int)ceil($total/$per):0);

        return [
            'items'=>$items,
            'meta'=>[
                'page'=>$page,'per_page'=>$per,'total'=>$total,'total_pages'=>$total_pages,
                'has_prev'=>$page>1,'has_next'=>$page<$total_pages
            ]
        ];
    }
}
