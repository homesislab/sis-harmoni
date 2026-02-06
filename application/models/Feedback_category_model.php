<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feedback_category_model extends CI_Model
{
    private string $table = 'feedback_categories';

    public function all_active(): array
    {
        return $this->db->from($this->table)->where('is_active',1)->order_by('sort_order','ASC')->order_by('id','ASC')->get()->result_array();
    }
}
