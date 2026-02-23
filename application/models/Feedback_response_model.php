<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Feedback_response_model extends MY_Model
{
    protected string $table_name = 'feedback_responses';

    private string $table = 'feedback_responses';

    public function list_for_feedback(int $feedback_id, bool $include_internal = false): array
    {
        $qb = $this->db->from($this->table)->where('feedback_id', $feedback_id)->order_by('id', 'ASC');
        if (!$include_internal) {
            $qb->where('is_public', 1);
        }
        return $qb->get()->result_array();
    }

    public function create(array $data): int
    {
        $payload = [
            'feedback_id' => (int)$data['feedback_id'],
            'responder_id' => isset($data['responder_id']) ? (int)$data['responder_id'] : null,
            'message' => (string)$data['message'],
            'is_public' => isset($data['is_public']) ? (int)$data['is_public'] : 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }
}
