<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feedbacks extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Feedback_model', 'FeedbackModel');
        $this->load->model('Feedback_response_model', 'ResponseModel');
    }

    public function index(): void
    {
        $this->require_any_permission(['app.services.resident.feedback.create']);
        $page = max(1, (int)$this->input->get('page'));
        $per = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $filters = [
            'person_id' => (int)($this->auth_user['person_id'] ?? 0),
            'status' => $this->input->get('status') ? (string)$this->input->get('status') : null,
            'q' => $this->input->get('q') ? (string)$this->input->get('q') : null,
        ];

        $res = $this->FeedbackModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function show(int $id = 0): void
    {
        $this->require_any_permission(['app.services.resident.feedback.create','app.services.info.feedback.view','app.services.info.feedback.respond']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $fb = $this->FeedbackModel->find_by_id($id);
        if (!$fb) {
            api_not_found();
            return;
        }

        if (!$this->has_permission('app.services.info.feedback.view')) {
            $my_pid = (int)($this->auth_user['person_id'] ?? 0);
            $row_pid = (int)($fb['person_id'] ?? 0);

            if ($my_pid <= 0 || $row_pid !== $my_pid) {
                api_error('FORBIDDEN', 'Anda tidak berhak melihat saran ini.', 403);
                return;
            }
        }

        $can_see_internal = $this->has_permission('app.services.info.feedback.view');
        $fb['responses'] = $this->ResponseModel->list_for_feedback($id, $can_see_internal);

        api_ok(['feedback' => $fb]);
    }
}
