<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLASS MY_Model
 * 
 * Provides base CRUD and common operations to DRY up child models.
 * Child models must declare `protected string $table_name;`
 */
class MY_Model extends CI_Model
{
    /** @var string Name of the database table (must be defined in child) */
    protected string $table_name = '';

    /**
     * Get a single record by primary key (id)
     */
    public function base_find_by_id(int $id): ?array
    {
        if ($this->table_name === '') return null;
        
        $row = $this->db->get_where($this->table_name, ['id' => $id])->row_array();
        return $row ?: null;
    }

    /**
     * Create a new record
     */
    public function base_create(array $data): int
    {
        if ($this->table_name === '') return 0;

        // Auto-fill created_at / updated_at if not provided, assuming standard naming
        if (!isset($data['created_at']) && $this->db->field_exists('created_at', $this->table_name)) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at']) && $this->db->field_exists('updated_at', $this->table_name)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table_name, $data);
        return (int)$this->db->insert_id();
    }

    /**
     * Update an existing record by id
     */
    public function base_update(int $id, array $data): bool
    {
        if ($this->table_name === '') return false;
        if (empty($data)) return true; // Nothing to update

        if (!isset($data['updated_at']) && $this->db->field_exists('updated_at', $this->table_name)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->where('id', $id)->update($this->table_name, $data);
    }

    /**
     * Delete a record by id
     */
    public function base_delete(int $id): bool
    {
        if ($this->table_name === '') return false;
        
        return $this->db->where('id', $id)->delete($this->table_name);
    }
    
    /**
     * Basic Pagination Helper
     */
    public function base_paginate(int $page = 1, int $per_page = 20, array $where = [], string $order_by = 'id', string $order_dir = 'DESC'): array
    {
        if ($this->table_name === '') return ['items' => [], 'total' => 0];

        $offset = ($page - 1) * $per_page;

        $qb = $this->db->from($this->table_name);
        if (!empty($where)) {
            $qb->where($where);
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->order_by($order_by, $order_dir)
            ->limit($per_page, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'total' => $total
        ];
    }
}
