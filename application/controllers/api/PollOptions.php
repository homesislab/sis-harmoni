<?php

defined('BASEPATH') or exit('No direct script access allowed');

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
        $this->require_any_permission(['app.services.info.polls.manage']);

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
        audit_log($this, 'Menambahkan opsi polling', 'Menambahkan opsi "' . $label . '" ke polling "' . ($poll['title'] ?? 'Tanpa judul') . '"');

        api_ok(['id' => (int)$id], null, 201);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);
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
        $old = trim((string)($opt['label'] ?? ''));
        if ($old === '') {
            $old = 'Opsi';
        }
        audit_log($this, 'Memperbarui opsi polling', 'Memperbarui opsi polling "' . ($poll['title'] ?? 'Tanpa judul') . '": "' . $old . '" → "' . $label . '"');

        api_ok(['ok' => true]);
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);
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
        $old = trim((string)($opt['label'] ?? ''));
        if ($old === '') {
            $old = 'Opsi';
        }
        audit_log($this, 'Menghapus opsi polling', 'Menghapus opsi "' . $old . '" dari polling "' . ($poll['title'] ?? 'Tanpa judul') . '"');

        api_ok(['ok' => true]);
    }
}
