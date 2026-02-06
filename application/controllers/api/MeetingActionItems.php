<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MeetingActionItems extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Meeting_action_item_model', 'ActionItemModel');
    }

    public function update(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ActionItemModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $this->ActionItemModel->update($id, $in);
        api_ok($this->ActionItemModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->ActionItemModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }
        $this->ActionItemModel->delete($id);
        api_ok(['ok'=>true]);
    }
}
