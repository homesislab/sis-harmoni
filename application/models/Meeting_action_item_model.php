<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meeting_action_item_model extends CI_Model
{
    private string $table = 'meeting_action_items';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where($this->table, ['id'=>$id])->row_array();
        return $row ?: null;
    }

    public function list_by_minutes(int $meeting_minute_id): array
    {
        return $this->db->from($this->table)
            ->where('meeting_minute_id', $meeting_minute_id)
            ->order_by('id','ASC')->get()->result_array();
    }

    public function create(array $data): int
    {
        $payload = [
            'meeting_minute_id' => (int)$data['meeting_minute_id'],
            'description' => (string)$data['description'],
            'pic_user_id' => isset($data['pic_user_id']) ? (int)$data['pic_user_id'] : null,
            'pic_person_id' => isset($data['pic_person_id']) ? (int)$data['pic_person_id'] : null,
            'due_at' => $data['due_at'] ?? null,
            'status' => $data['status'] ?? 'open',
            'done_at' => $data['done_at'] ?? null,
            'note' => $data['note'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['description','pic_user_id','pic_person_id','due_at','status','done_at','note'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k,$data)) $upd[$k] = $data[$k];
        }
        if ($upd) {
            if (isset($upd['status']) && $upd['status'] === 'done' && !isset($upd['done_at'])) {
                $upd['done_at'] = date('Y-m-d H:i:s');
            }
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id',$id)->update($this->table,$upd);
        }
    }

    public function delete(int $id): void
    {
        $this->db->where('id',$id)->delete($this->table);
    }
}
