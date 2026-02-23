<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Poll_vote_model extends MY_Model
{
    protected string $table_name = 'poll_votes';


    public function create_vote(array $data): bool
    {
        $this->db->trans_begin();

        try {
            if ($data['vote_scope'] === 'household') {
                $exists = $this->db->get_where('poll_votes', [
                    'poll_id' => (int)$data['poll_id'],
                    'household_id' => (int)$data['household_id'],
                ])->row_array();
                if ($exists) {
                    $this->db->trans_rollback();
                    return false;
                }
            } else {
                $exists = $this->db->get_where('poll_votes', [
                    'poll_id' => (int)$data['poll_id'],
                    'user_id' => (int)$data['user_id'],
                ])->row_array();
                if ($exists) {
                    $this->db->trans_rollback();
                    return false;
                }
            }

            $this->db->insert('poll_votes', [
                'poll_id' => (int)$data['poll_id'],
                'option_id' => (int)$data['option_id'],
                'user_id' => (int)$data['user_id'],
                'household_id' => $data['household_id'] ? (int)$data['household_id'] : null,
            ]);

            $this->db->trans_commit();
            return true;

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'create_vote error: ' . $e->getMessage());
            return false;
        }
    }
}
