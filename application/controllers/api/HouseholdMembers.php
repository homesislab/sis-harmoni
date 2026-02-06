<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HouseholdMembers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Household_model', 'households');
        $this->load->helper(['api_response','api']);
        $this->load->library('form_validation');
    }

    public function store(): void
    {
        $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post(NULL, true);

        $household_id = (int)($input['household_id'] ?? 0);
        $person_id    = (int)($input['person_id'] ?? 0);
        $role         = (string)($input['role'] ?? 'member');

        if ($household_id <= 0 || $person_id <= 0) {
            api_fail('VALIDATION_ERROR', 'household_id dan person_id wajib diisi', null, 422);
        }

        $ok = $this->households->add_member($household_id, $person_id, $role);
        if (!$ok) {
            api_fail('ADD_MEMBER_FAILED', 'Gagal menambahkan anggota household', null, 400);
        }

        $detail = $this->households->find_detail($household_id);
        api_ok($detail);
    }

    public function update(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $input = json_decode($this->input->raw_input_stream, true) ?: $this->input->post(NULL, true);
        $relationship = trim((string)($input['relationship'] ?? ($input['role'] ?? '')));
        if ($relationship === '') {
            api_fail('VALIDATION_ERROR', 'relationship wajib diisi', null, 422);
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
        if ($id <= 0) { api_not_found(); return; }

        $ok = $this->households->remove_member($id);
        if (!$ok) {
            api_not_found();
            return;
        }

        api_ok(null, ['message' => 'Anggota dihapus']);
    }
}
