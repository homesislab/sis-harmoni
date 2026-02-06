<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FundraiserUpdates extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Fundraiser_model', 'FundraiserModel');
        $this->load->model('Fundraiser_update_model', 'UpdateModel');
    }

    public function index(int $fundraiser_id = 0): void
    {
        if ($fundraiser_id <= 0) {
            api_not_found();
            return;
        }

        $fund = $this->FundraiserModel->find_by_id($fundraiser_id);
        if (!$fund) {
            api_not_found('Fundraiser tidak ditemukan');
            return;
        }

        $items = $this->UpdateModel->list_by_fundraiser($fundraiser_id);
        api_ok(['items' => $items]);
    }

    public function store(): void
    {
        $this->require_role(['admin']);

        $in = $this->json_input();
        $err = $this->UpdateModel->validate_payload($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $fund = $this->FundraiserModel->find_by_id((int)$in['fundraiser_id']);
        if (!$fund) {
            api_validation_error(['fundraiser_id' => 'Fundraiser tidak ditemukan']);
            return;
        }

        $id = $this->UpdateModel->create($in);
        audit_log($this, 'fundraiser_update_create', 'Create update #' . $id);
        api_ok($this->UpdateModel->find_by_id($id), null, 201);
    }

    public function update(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->UpdateModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $err = $this->UpdateModel->validate_payload($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $this->UpdateModel->update($id, $in);
        audit_log($this, 'fundraiser_update_update', 'Update update #' . $id);

        api_ok($this->UpdateModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->UpdateModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->UpdateModel->delete($id);
        audit_log($this, 'fundraiser_update_delete', 'Delete update #' . $id);
        api_ok(['ok' => true]);
    }
}
