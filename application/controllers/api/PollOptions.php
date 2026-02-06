<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PollOptions extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Poll_model', 'PollModel');
    }

    public function store(): void
    {
        $this->require_any_permission(['poll.manage']);

        $in = $this->json_input();
        $poll_id = (int)($in['poll_id'] ?? 0);
        $label = trim((string)($in['label'] ?? ''));

        $err = [];
        if ($poll_id <= 0) {
            $err['poll_id'] = 'Wajib diisi';
        }
        if ($label === '') {
            $err['label'] = 'Wajib diisi';
        }
        if (!empty($err)) {
            api_validation_error($err);
            return;
        }

        $poll = $this->PollModel->find_poll($poll_id);
        if (!$poll) {
            api_validation_error(['poll_id' => 'Poll tidak ditemukan']);
            return;
        }
        if (($poll['status'] ?? '') !== 'draft') {
            api_error('FORBIDDEN', 'Opsi hanya bisa ditambah saat draft', 403);
            return;
        }

        $id = $this->PollModel->create_option($poll_id, $label);
        audit_log($this, 'poll_option_create', "Add option #$id to poll #$poll_id");

        api_ok(['id' => (int)$id], null, 201);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['poll.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $label = trim((string)($in['label'] ?? ''));
        if ($label === '') {
            api_validation_error(['label' => 'Wajib diisi']);
            return;
        }

        $opt = $this->PollModel->find_option($id);
        if (!$opt) {
            api_not_found();
            return;
        }

        $poll = $this->PollModel->find_poll((int)($opt['poll_id'] ?? 0));
        if (!$poll) {
            api_error('NOT_FOUND', 'Poll tidak ditemukan', 404);
            return;
        }
        if (($poll['status'] ?? '') !== 'draft') {
            api_error('FORBIDDEN', 'Opsi hanya bisa diubah saat draft', 403);
            return;
        }

        $this->PollModel->update_option($id, $label);
        audit_log($this, 'poll_option_update', "Update option #$id");

        api_ok(['ok' => true]);
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['poll.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $opt = $this->PollModel->find_option($id);
        if (!$opt) {
            api_not_found();
            return;
        }

        $poll = $this->PollModel->find_poll((int)($opt['poll_id'] ?? 0));
        if (!$poll) {
            api_error('NOT_FOUND', 'Poll tidak ditemukan', 404);
            return;
        }
        if (($poll['status'] ?? '') !== 'draft') {
            api_error('FORBIDDEN', 'Opsi hanya bisa dihapus saat draft', 403);
            return;
        }

        $this->PollModel->delete_option($id);
        audit_log($this, 'poll_option_delete', "Delete option #$id");

        api_ok(['ok' => true]);
    }
}
