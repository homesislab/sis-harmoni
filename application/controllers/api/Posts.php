<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Posts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Post_model', 'PostModel');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $filters = [
            'org' => $this->input->get('org') ? (string)$this->input->get('org') : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        $can_manage = $this->has_permission('app.services.info.posts.manage');
        $filters['status'] = $can_manage
            ? ($this->input->get('status') ? (string)$this->input->get('status') : null)
            : 'published';

        $res = $this->PostModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.services.info.posts.manage']);

        $in = $this->json_input();
        $err = $this->PostModel->validate_payload($in, true);
        if ($err) { api_validation_error($err); return; }

        $id = $this->PostModel->create($in, (int)$this->auth_user['id']);

        $title = trim((string)($in['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Menambahkan posting', 'Menambahkan posting "' . $title . '"');

        api_ok($this->PostModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->PostModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        if (!$this->has_permission('app.services.info.posts.manage') && $row['status'] !== 'published') {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.posts.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->PostModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $err = $this->PostModel->validate_payload($in, false);
        if ($err) { api_validation_error($err); return; }

        $this->PostModel->update($id, $in);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Memperbarui posting', 'Memperbarui posting "' . $title . '"');

        api_ok($this->PostModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.posts.manage']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->PostModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->PostModel->delete($id);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') $title = 'Tanpa judul';
        audit_log($this, 'Menghapus posting', 'Menghapus posting "' . $title . '"');

        api_ok(null, ['message' => 'Post dihapus']);
    }
}
