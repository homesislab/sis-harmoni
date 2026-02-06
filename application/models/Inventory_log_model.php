<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_log_model extends CI_Model
{
    private string $table = 'inventory_logs';

    public function create(array $data): int
    {
        $payload = [
            'inventory_id' => (int)$data['inventory_id'],
            'action' => (string)$data['action'],
            'qty_change' => array_key_exists('qty_change', $data) ? (int)$data['qty_change'] : null,
            'from_location' => $data['from_location'] ?? null,
            'to_location' => $data['to_location'] ?? null,
            'borrower_person_id' => isset($data['borrower_person_id']) ? (int)$data['borrower_person_id'] : null,
            'borrower_house_id' => isset($data['borrower_house_id']) ? (int)$data['borrower_house_id'] : null,
            'note' => $data['note'] ?? null,
            'actor_user_id' => isset($data['actor_user_id']) ? (int)$data['actor_user_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function list_by_inventory(int $inventory_id, int $limit = 50): array
    {
        return $this->db
            ->from($this->table)
            ->where('inventory_id', $inventory_id)
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }
}
