<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Posts extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Post_model', 'PostModel');
        $this->load->library('push_notification');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $filters = [
            'org' => $this->constrain_org_filter($this->input->get('org') ? (string)$this->input->get('org') : null),
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
        $in['org'] = $this->constrain_org_filter($in['org'] ?? null) ?? 'paguyuban';

        $err = $this->PostModel->validate_payload($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->PostModel->create($in, (int)$this->auth_user['id']);

        $title = trim((string)($in['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Menambahkan posting', 'Menambahkan posting "' . $title . '"');

        if (($in['status'] ?? 'published') === 'published') {
            $this->push_notification->send_to_all(
                'Info warga baru',
                $title,
                '/community/posts/' . $id,
                ['type' => 'post_published', 'post_id' => (string)$id]
            );
        }

        api_ok($this->PostModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->PostModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->require_org_access($row['org'] ?? null);

        if (!$this->has_permission('app.services.info.posts.manage') && $row['status'] !== 'published') {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.posts.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->PostModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->require_org_access($row['org'] ?? null);

        $in = $this->json_input();
        if (array_key_exists('org', $in)) {
            $in['org'] = $this->constrain_org_filter($in['org']);
        }

        $err = $this->PostModel->validate_payload($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $wasPublished = (($row['status'] ?? '') === 'published');
        $this->PostModel->update($id, $in);
        $next = $this->PostModel->find_by_id($id);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Memperbarui posting', 'Memperbarui posting "' . $title . '"');

        if (!$wasPublished && (($next['status'] ?? '') === 'published')) {
            $this->push_notification->send_to_all(
                'Info warga baru',
                trim((string)($next['title'] ?? $title)),
                '/community/posts/' . $id,
                ['type' => 'post_published', 'post_id' => (string)$id]
            );
        }

        api_ok($next);
    }

    public function destroy(int $id = 0): void
    {
        $this->require_any_permission(['app.services.info.posts.manage']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->PostModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->require_org_access($row['org'] ?? null);

        $this->PostModel->delete($id);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Menghapus posting', 'Menghapus posting "' . $title . '"');

        api_ok(null, ['message' => 'Post dihapus']);
    }
}
