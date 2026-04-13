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
        $this->load->library('whatsapp');
        $this->load->library('push_notification');
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

        $this->require_org_access($fund['category'] ?? null);
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

        $this->require_org_access($fund['category'] ?? null);

        $createdBy = 0;
        if (property_exists($this, 'user') && is_array($this->user) && isset($this->user['id'])) {
            $createdBy = (int)$this->user['id'];
        }
        if ($createdBy <= 0 && property_exists($this, 'auth_user') && is_array($this->auth_user) && isset($this->auth_user['id'])) {
            $createdBy = (int)$this->auth_user['id'];
        }

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

        if (($fund['status'] ?? '') === 'active') {
            $this->push_notification->send_to_all(
                'Update program donasi',
                $fundTitle . ': ' . $updTitle,
                '/services/donations/' . (int)$fund['id'],
                [
                    'type' => 'fundraiser_update',
                    'fundraiser_id' => (string)$fund['id'],
                    'fundraiser_update_id' => (string)$id,
                ]
            );
        }

        $donors = $this->db->select('DISTINCT(person_id) AS pid')->from('fundraiser_donations')->where('fundraiser_id', $fund['id'])->where('status', 'approved')->get()->result_array();
        foreach ($donors as $dn) {
            $pid = (int)$dn['pid'];
            if ($pid > 0) {
                $pRow = $this->db->get_where('persons', ['id' => $pid])->row_array();
                if ($pRow && !empty($pRow['phone'])) {
                    $nama = $pRow['full_name'] ?? 'Warga';
                    $wa_msg = "Assalamu’alaikum, {$nama}

Terdapat informasi terbaru mengenai program donasi *{$fundTitle}*:
_{$updTitle}_

Jazakumullah khairan katsiran atas doa dan dukungannya.

—
Pesan ini dikirim otomatis melalui layanan SIS Harmoni";
                    $this->whatsapp->send_message($pRow['phone'], $wa_msg);
                }
            }
        }

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

        $fund = $this->FundraiserModel->find_by_id((int)($row['fundraiser_id'] ?? 0));
        if ($fund) {
            $this->require_org_access($fund['category'] ?? null);
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

        $fund = $this->FundraiserModel->find_by_id((int)($row['fundraiser_id'] ?? 0));
        if ($fund) {
            $this->require_org_access($fund['category'] ?? null);
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
