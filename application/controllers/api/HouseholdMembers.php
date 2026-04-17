<?php

defined('BASEPATH') or exit('No direct script access allowed');

class HouseholdMembers extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Household_model', 'households');
        $this->load->model('Person_model', 'PersonModel');
        $this->load->helper(['api_response','api']);
        $this->load->library('form_validation');
    }

    public function store(): void
    {
        $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post(null, true);

        $household_id = (int)($input['household_id'] ?? 0);
        $person_id    = (int)($input['person_id'] ?? 0);
        $role         = (string)($input['relationship'] ?? ($input['role'] ?? 'anggota'));
        $person       = isset($input['person']) && is_array($input['person']) ? $input['person'] : null;

        if ($household_id <= 0) {
            api_validation_error(['household_id' => 'Wajib diisi']);
            return;
        }

        if ($person_id <= 0 && !$person) {
            api_validation_error(['person_id' => 'Wajib diisi']);
            return;
        }

        $this->db->trans_begin();
        if ($person_id <= 0 && $person) {
            $errors = $this->PersonModel->validate_payload($person, true);
            if ($errors) {
                $this->db->trans_rollback();
                api_validation_error($errors);
                return;
            }
            $person_id = $this->PersonModel->create($person);
        }

        $ok = $this->households->add_member($household_id, $person_id, $role);
        if (!$ok) {
            $this->db->trans_rollback();
            api_fail('ADD_MEMBER_FAILED', 'Gagal menambahkan anggota household', null, 400);
            return;
        }

        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            api_error('SERVER_ERROR', 'Terjadi kesalahan pada server', 500);
            return;
        }

        $this->db->trans_commit();
        $detail = $this->households->find_detail($household_id);
        api_ok($detail);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post(null, true);
        $relationship = trim((string)($input['relationship'] ?? ($input['role'] ?? '')));
        if ($relationship === '') {
            api_fail('VALIDATION_ERROR', 'relationship wajib diisi', null, 422);
            return;
        }

        $member = $this->db->get_where('household_members', ['id' => $id])->row_array();
        if (!$member) {
            api_not_found();
            return;
        }

        $person = isset($input['person']) && is_array($input['person']) ? $input['person'] : null;
        if ($person) {
            $errors = $this->PersonModel->validate_payload($person, false);
            if ($errors) {
                api_validation_error($errors);
                return;
            }
            $this->PersonModel->update((int)$member['person_id'], $person);
        }

        $row = $this->households->update_member_relationship($id, $relationship);
        if (!$row) {
            api_not_found();
            return;
        }

        api_ok($row);
    }

    public function destroy(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $ok = $this->households->remove_member($id);
        if (!$ok) {
            api_not_found();
            return;
        }

        api_ok(null, ['message' => 'Anggota dihapus']);
    }
}
