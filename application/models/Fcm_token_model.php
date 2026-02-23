<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Fcm_token_model extends CI_Model
{
    protected $table = 'fcm_tokens';

    public function __construct()
    {
        parent::__construct();
    }

    public function save_token(int $user_id, string $token, ?string $device_type = null): void
    {
        // Try to update existing token for this user
        $existing = $this->db->get_where($this->table, [
            'user_id' => $user_id,
            'token' => $token
        ])->row_array();

        if ($existing) {
            $this->db->update($this->table, [
                'device_type' => $device_type,
                'last_active' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $existing['id']]);
        } else {
            $this->db->insert($this->table, [
                'user_id' => $user_id,
                'token' => $token,
                'device_type' => $device_type,
                'last_active' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function remove_token(string $token): void
    {
        $this->db->delete($this->table, ['token' => $token]);
    }

    public function get_tokens_except_user(int $excluded_user_id): array
    {
        $this->db->select('token');
        $this->db->where('user_id !=', $excluded_user_id);
        $this->db->group_by('token');
        $query = $this->db->get($this->table);
        
        $tokens = [];
        foreach ($query->result_array() as $row) {
            $tokens[] = $row['token'];
        }
        return $tokens;
    }
}
