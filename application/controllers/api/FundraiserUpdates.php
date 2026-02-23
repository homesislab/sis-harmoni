<?php

defined('BASEPATH') or exit('No direct script access allowed');

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
        $this->require_permission('app.services.finance.donation_campaigns.manage');

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

        // Ambil user id login dari MY_Controller (sesuaikan dengan implementasimu)
        // Pilih salah satu yang memang ada di project kamu:
        $createdBy = 0;

        // opsi 1 (paling umum kalau MY_Controller simpan user):
        if (property_exists($this, 'user') && is_array($this->user) && isset($this->user['id'])) {
            $createdBy = (int)$this->user['id'];
        }

        // opsi 2 (kalau ada auth user):
        if ($createdBy <= 0 && property_exists($this, 'auth_user') && is_array($this->auth_user) && isset($this->auth_user['id'])) {
            $createdBy = (int)$this->auth_user['id'];
        }

        // opsi 3 (kalau ada helper method di MY_Controller):
        // if ($createdBy <= 0 && method_exists($this, 'auth_user_id')) $createdBy = (int)$this->auth_user_id();

        if ($createdBy <= 0) {
            api_validation_error(['auth' => 'User login tidak valid']);
            return;
        }

        $id = $this->UpdateModel->create($in, $createdBy);

        $updTitle = trim((string)($in['title'] ?? ''));
        if ($updTitle === '') {
            $updTitle = 'Tanpa judul';
        }
        $fundTitle = trim((string)($fund['title'] ?? ''));
        if ($fundTitle === '') {
            $fundTitle = 'Program donasi';
        }

        audit_log($this, 'Menambahkan update donasi', 'Menambahkan update "' . $updTitle . '" untuk "' . $fundTitle . '"');
        api_ok($this->UpdateModel->find_by_id($id), null, 201);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.finance.donation_campaigns.manage');
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
        $updTitle = trim((string)($row['title'] ?? ''));
        if ($updTitle === '') {
            $updTitle = 'Tanpa judul';
        }
        audit_log($this, 'Memperbarui update donasi', 'Memperbarui update donasi "' . $updTitle . '"');

        api_ok($this->UpdateModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_permission('app.services.finance.donation_campaigns.manage');
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
        $updTitle = trim((string)($row['title'] ?? ''));
        if ($updTitle === '') {
            $updTitle = 'Tanpa judul';
        }
        audit_log($this, 'Menghapus update donasi', 'Menghapus update donasi "' . $updTitle . '"');
        api_ok(['ok' => true]);
    }
}
