<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Polls extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Poll_model', 'PollModel');
        $this->load->model('Poll_vote_model', 'VoteModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $can_manage = $this->has_permission('app.services.info.polls.manage');

        $filters = ['q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null];

        if ($can_manage) $filters['status'] = $this->input->get('status') ? (string)$this->input->get('status') : null;
        else $filters['status_in'] = ['published','closed'];

        $res = $this->PollModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);

        $in = $this->json_input();
        $err = $this->PollModel->validate_poll($in, true);
        if ($err) { api_validation_error($err); return; }

        $id = $this->PollModel->create_poll($in, (int)$this->auth_user['id']);

        $title = trim((string)($in['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Membuat polling', 'Membuat polling "' . $title . '"');

        api_ok($this->PollModel->find_detail($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->PollModel->find_detail($id);
        if (!$row) { api_not_found(); return; }

        if (!$this->has_permission('app.services.info.polls.manage')) {
            if (!in_array($row['poll']['status'], ['published','closed'], true)) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->PollModel->find_poll($id);
        if (!$row) { api_not_found(); return; }

        if ($row['status'] !== 'draft') {
            api_error('FORBIDDEN', 'Poll yang sudah dipublish tidak bisa diubah (kecuali close)', 403);
            return;
        }

        $in = $this->json_input();
        $err = $this->PollModel->validate_poll($in, false);
        if ($err) { api_validation_error($err); return; }

        $this->PollModel->update_poll($id, $in);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Memperbarui polling', 'Memperbarui polling "' . $title . '"');

        api_ok($this->PollModel->find_detail($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->PollModel->find_poll($id);
        if (!$row) { api_not_found(); return; }

        if ($row['status'] !== 'draft') {
            api_error('FORBIDDEN', 'Hanya poll draft yang boleh dihapus', 403);
            return;
        }

        $this->PollModel->delete_poll($id);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Menghapus polling', 'Menghapus polling "' . $title . '"');

        api_ok(null, ['message' => 'Poll dihapus']);
    }

    public function publish(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $poll = $this->PollModel->find_poll($id);
        if (!$poll) { api_not_found(); return; }

        if ($poll['status'] !== 'draft') { api_conflict('Poll sudah dipublish/closed'); return; }

        $optCount = $this->PollModel->count_options($id);
        if ($optCount < 2) {
            api_validation_error(['options' => 'Minimal 2 opsi diperlukan sebelum publish']);
            return;
        }

        $this->PollModel->set_status($id, 'published');

        $title = trim((string)($poll['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Mempublish polling', 'Mempublish polling "' . $title . '"');

        api_ok(null, ['message' => 'Poll dipublish']);
    }

    public function close(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.polls.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $poll = $this->PollModel->find_poll($id);
        if (!$poll) { api_not_found(); return; }

        if ($poll['status'] === 'closed') { api_conflict('Poll sudah closed'); return; }

        $this->PollModel->set_status($id, 'closed');

        $title = trim((string)($poll['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Menutup polling', 'Menutup polling "' . $title . '"');

        api_ok(null, ['message' => 'Poll ditutup']);
    }

    public function vote(int $pollId = 0): void
    {
        $this->require_permission('app.home.polls');
        if ($pollId <= 0) { api_not_found(); return; }

        if (empty($this->auth_user['person_id'])) {
            api_error('FORBIDDEN', 'Akun belum terhubung ke data warga (person_id)', 403);
            return;
        }

        $poll = $this->PollModel->find_poll($pollId);
        if (!$poll) { api_not_found('Poll tidak ditemukan'); return; }

        if ($poll['status'] !== 'published') { api_error('FORBIDDEN','Poll belum dibuka / sudah ditutup',403); return; }

        $now = date('Y-m-d H:i:s');
        if ($now < $poll['start_at'] || $now > $poll['end_at']) {
            api_error('FORBIDDEN', 'Voting di luar waktu yang ditentukan', 403);
            return;
        }

        $in = $this->json_input();
        $option_id = (int)($in['option_id'] ?? 0);
        if ($option_id <= 0) { api_validation_error(['option_id'=>'Wajib diisi']); return; }

        $opt = $this->PollModel->find_option($option_id);
        if (!$opt || (int)$opt['poll_id'] !== $pollId) {
            api_validation_error(['option_id'=>'Opsi tidak valid untuk poll ini']);
            return;
        }

        $user_id = (int)$this->auth_user['id'];
        $person_id = (int)$this->auth_user['person_id'];

        $household_id = null;
        if ($poll['vote_scope'] === 'household') {
            $household_id = $this->PollModel->resolve_household_id_for_person($person_id);
            if (!$household_id) {
                api_error('FORBIDDEN', 'Voting scope household: data household belum terdaftar', 403);
                return;
            }
        }

        $ok = $this->VoteModel->create_vote([
            'poll_id' => $pollId,
            'option_id' => $option_id,
            'user_id' => $user_id,
            'household_id' => $household_id,
            'vote_scope' => $poll['vote_scope'],
        ]);

        if (!$ok) {
            api_conflict('Anda sudah melakukan voting untuk poll ini');
            return;
        }

        $pollTitle = trim((string)($poll['title'] ?? ''));
        if ($pollTitle === '') $pollTitle = 'Polling';
        $optLabel = trim((string)($opt['label'] ?? ''));
        if ($optLabel === '') $optLabel = 'Opsi';
        audit_log($this, 'Mengisi polling', 'Mengisi polling "' . $pollTitle . '" memilih "' . $optLabel . '"');

        api_ok(['message' => 'Vote berhasil disimpan'], null, 201);
    }

    public function my_vote(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        if (empty($this->auth_user['person_id'])) {
            api_error('FORBIDDEN', 'Akun belum terhubung ke data warga (person_id)', 403);
            return;
        }

        $poll = $this->PollModel->find_poll($id);
        if (!$poll) { api_not_found('Poll tidak ditemukan'); return; }

        if (!$this->has_permission('app.services.info.polls.manage')) {
            if (!in_array($poll['status'], ['published','closed'], true)) {
                api_error('FORBIDDEN', 'Akses ditolak', 403);
                return;
            }
        }

        $person_id = (int)$this->auth_user['person_id'];
        $user_id = (int)$this->auth_user['id'];

        $row = null;

        if ($poll['vote_scope'] === 'household') {
            $household_id = $this->PollModel->resolve_household_id_for_person($person_id);
            if (!$household_id) {
                api_ok([
                    'has_voted' => false,
                    'vote_scope' => 'household',
                    'household_id' => null,
                    'option_id' => null,
                ]);
                return;
            }

            $row = $this->db->select('option_id, household_id')
                ->from('poll_votes')
                ->where('poll_id', $id)
                ->where('household_id', (int)$household_id)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()->row_array();

            api_ok([
                'has_voted' => (bool)$row,
                'vote_scope' => 'household',
                'household_id' => (int)$household_id,
                'option_id' => $row ? (int)$row['option_id'] : null,
            ]);
            return;
        }

        $row = $this->db->select('option_id, user_id')
            ->from('poll_votes')
            ->where('poll_id', $id)
            ->where('user_id', (int)$user_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();

        api_ok([
            'has_voted' => (bool)$row,
            'vote_scope' => 'user',
            'user_id' => (int)$user_id,
            'option_id' => $row ? (int)$row['option_id'] : null,
        ]);
    }

    public function results(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $poll = $this->PollModel->find_poll($id);
        if (!$poll) { api_not_found(); return; }

        if (!$this->has_permission('app.services.info.polls.manage')) {
            if (!in_array($poll['status'], ['published','closed'], true)) {
                api_error('FORBIDDEN','Akses ditolak',403);
                return;
            }
        }

        $data = $this->PollModel->get_results($id);
        api_ok($data);
    }
}
