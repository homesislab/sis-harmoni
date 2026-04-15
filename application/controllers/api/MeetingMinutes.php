<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MeetingMinutes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Meeting_minute_model', 'MinutesModel');
        $this->load->model('Meeting_action_item_model', 'ActionItemModel');
        $this->load->library('push_notification');
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)($this->input->get('per_page') ?: 20)));

        $can_manage = $this->has_permission('app.services.notes.meeting_minutes.manage');

        $filters = [
            'q' => $this->input->get('q') ? (string)$this->input->get('q') : null,
            'status' => $can_manage
                ? ($this->input->get('status') ? (string)$this->input->get('status') : null)
                : 'published',
        ];

        $res = $this->MinutesModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.notes.meeting_minutes.manage');
        $in = $this->json_input();
        if (!isset($in['location_text']) && isset($in['location'])) {
            $in['location_text'] = $in['location'];
        }
        $err = [];
        if (empty($in['title'])) {
            $err['title'] = 'Wajib diisi';
        }
        if (empty($in['meeting_at'])) {
            $err['meeting_at'] = 'Wajib diisi';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        if (isset($in['decisions']) && is_array($in['decisions'])) {
            $in['decisions'] = implode("\n", array_map('strval', $in['decisions']));
        }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];
        $id = $this->MinutesModel->create($payload);
        $next = $this->MinutesModel->find_by_id($id);
        if (($next['status'] ?? '') === 'published') {
            $slug = (string)$id . '-' . (function_exists('slugify_text') ? slugify_text($next['title'] ?? 'notulen-rapat') : 'notulen-rapat');
            $this->push_notification->send_to_all(
                'Notulen rapat baru',
                trim((string)($next['title'] ?? 'Notulen rapat')),
                '/share/notulen/' . $slug,
                ['type' => 'meeting_minute_published', 'meeting_minute_id' => (string)$id]
            );
        }
        api_ok($next, null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->MinutesModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        if (!$this->has_permission('app.services.notes.meeting_minutes.manage') && ($row['status'] ?? '') !== 'published') {
            api_not_found();
            return;
        }

        $items = $this->ActionItemModel->list_by_minutes($id);
        api_ok(['meeting_minutes' => $row, 'action_items' => $items]);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.notes.meeting_minutes.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->MinutesModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        if (!isset($in['location_text']) && isset($in['location'])) {
            $in['location_text'] = $in['location'];
        }
        if (isset($in['decisions']) && is_array($in['decisions'])) {
            $in['decisions'] = implode("\n", array_map('strval', $in['decisions']));
        }
        $wasPublished = (($row['status'] ?? '') === 'published');
        $this->MinutesModel->update($id, $in);
        $next = $this->MinutesModel->find_by_id($id);
        if (!$wasPublished && (($next['status'] ?? '') === 'published')) {
            $slug = (string)$id . '-' . (function_exists('slugify_text') ? slugify_text($next['title'] ?? 'notulen-rapat') : 'notulen-rapat');
            $this->push_notification->send_to_all(
                'Notulen rapat baru',
                trim((string)($next['title'] ?? 'Notulen rapat')),
                '/share/notulen/' . $slug,
                ['type' => 'meeting_minute_published', 'meeting_minute_id' => (string)$id]
            );
        }
        api_ok($next);
    }

    public function destroy(int $id = 0): void
    {
        $this->require_permission('app.services.notes.meeting_minutes.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->MinutesModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }
        $this->MinutesModel->delete($id);
        api_ok(['ok' => true]);
    }

    public function action_items_create(int $id = 0): void
    {
        $this->require_permission('app.services.notes.meeting_minutes.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $m = $this->MinutesModel->find_by_id($id);
        if (!$m) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        if (empty($in['description']) && !empty($in['title'])) {
            $in['description'] = $in['title'];
        }
        if (!isset($in['note']) && isset($in['notes'])) {
            $in['note'] = $in['notes'];
        }
        $err = [];
        if (empty($in['description'])) {
            $err['description'] = 'Wajib diisi';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        $payload = $in;
        $payload['meeting_minute_id'] = $id;
        $new_id = $this->ActionItemModel->create($payload);
        api_ok($this->ActionItemModel->find_by_id($new_id), null, 201);
    }
}
