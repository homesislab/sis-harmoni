<?php

defined('BASEPATH') or exit('No direct script access allowed');

class FundraiserDonations extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Fundraiser_model', 'FundraiserModel');
        $this->load->model('Donation_model', 'DonationModel');
        $this->load->model('Ledger_model', 'LedgerModel');
        $this->load->library('whatsapp');
    }

    public function index_admin(): void
    {
        $this->require_any_permission(['app.services.finance.donations.verify']);

        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $status = trim((string)$this->input->get('status'));
        $q = trim((string)$this->input->get('q'));

        $status = $status !== '' ? strtolower($status) : '';
        if ($status && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            api_validation_error(['status' => 'Nilai tidak valid']);
            return;
        }

        $filters = [
            'status' => ($status !== '' ? $status : null),
            'q' => ($q !== '' ? $q : null),
        ];

        $res = $this->DonationModel->paginate_admin($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function approve(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.donations.verify']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $don = $this->DonationModel->find_by_id($id);
        if (!$don) {
            api_not_found('Donation tidak ditemukan');
            return;
        }
        if (($don['status'] ?? '') !== 'pending') {
            api_conflict('Donation sudah diproses');
            return;
        }

        $fund = $this->FundraiserModel->find_by_id((int)$don['fundraiser_id']);
        if (!$fund) {
            api_error('SERVER_ERROR', 'Fundraiser missing', 500);
            return;
        }

        $this->db->trans_begin();

        try {
            $ledger_account_id = $this->LedgerModel->ensure_default_account($fund['category']);

            $ledger_entry_id = $this->LedgerModel->create_entry([
                'ledger_account_id' => $ledger_account_id,
                'direction' => 'in',
                'amount' => (float)$don['amount'],
                'category' => 'fundraiser_donation',
                'description' => 'Donasi fundraiser #' . $don['fundraiser_id'] . ' - ' . $fund['title'],
                'occurred_at' => $don['paid_at'],
                'source_type' => 'fundraiser_donation',
                'source_id' => (int)$don['id'],
                'created_by' => (int)$this->auth_user['id'],
            ]);

            $this->DonationModel->approve($id, (int)$this->auth_user['id']);
            $this->FundraiserModel->add_collected((int)$don['fundraiser_id'], (float)$don['amount']);

            $this->db->trans_commit();

            $donor = ((int)($don['is_anonymous'] ?? 0) === 1) ? 'Anonim' : trim((string)($don['full_name'] ?? 'Warga'));
            if ($donor === '') {
                $donor = 'Warga';
            }
            $fundTitle = trim((string)($fund['title'] ?? ($don['fundraiser_title'] ?? '')));
            if ($fundTitle === '') {
                $fundTitle = 'Program donasi';
            }
            $amt = number_format((float)($don['amount'] ?? 0), 0, ',', '.');
            audit_log($this, 'Menyetujui donasi', 'Menyetujui donasi Rp ' . $amt . ' untuk "' . $fundTitle . '" dari ' . $donor);

            // Send WA Notification
            $person_id = (int)($don['person_id'] ?? 0);
            if ($person_id > 0) {
                $pRow = $this->db->get_where('persons', ['id' => $person_id])->row_array();
                if ($pRow && !empty($pRow['phone'])) {
                    $nama = $pRow['full_name'] ?? 'Warga';
                    $wa_msg = "*[Info SIS]*\n\nAssalamu'alaikum, *{$nama}*,\n\n✅ Alhamdulillah, donasi Anda sebesar *Rp {$amt}* untuk program *{$fundTitle}* sudah *DITERIMA & DISETUJUI*.\n\nJazakumullah khairan katsiran atas partisipasi Anda, semoga berkah!";
                    $this->whatsapp->send_message($pRow['phone'], $wa_msg);
                }
            }

            api_ok(null, ['message' => 'Donation disetujui', 'ledger_entry_id' => $ledger_entry_id]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'approve_donation error: ' . $e->getMessage());
            api_error('SERVER_ERROR', 'Terjadi kesalahan pada server', 500);
        }
    }

    public function reject(int $id = 0): void
    {
        $this->require_any_permission(['app.services.finance.donations.verify']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $don = $this->DonationModel->find_by_id($id);
        if (!$don) {
            api_not_found('Donation tidak ditemukan');
            return;
        }
        if (($don['status'] ?? '') !== 'pending') {
            api_conflict('Donation sudah diproses');
            return;
        }

        $in = $this->json_input();
        $note = isset($in['note']) ? trim((string)$in['note']) : null;

        $this->DonationModel->reject($id, (int)$this->auth_user['id'], $note);

        $donor = ((int)($don['is_anonymous'] ?? 0) === 1) ? 'Anonim' : trim((string)($don['full_name'] ?? 'Warga'));
        if ($donor === '') {
            $donor = 'Warga';
        }
        $fundTitle = trim((string)($don['fundraiser_title'] ?? ''));
        if ($fundTitle === '') {
            $fundTitle = 'Program donasi';
        }
        $amt = number_format((float)($don['amount'] ?? 0), 0, ',', '.');
        audit_log($this, 'Menolak donasi', 'Menolak donasi Rp ' . $amt . ' untuk "' . $fundTitle . '" dari ' . $donor);

        // Send WA Notification
        $person_id = (int)($don['person_id'] ?? 0);
        if ($person_id > 0) {
            $pRow = $this->db->get_where('persons', ['id' => $person_id])->row_array();
            if ($pRow && !empty($pRow['phone'])) {
                $nama = $pRow['full_name'] ?? 'Warga';
                $wa_msg = "*[Info SIS]*\n\nAssalamu'alaikum, *{$nama}*,\n\n❌ Mohon maaf, konfirmasi donasi Anda sebesar *Rp {$amt}* untuk program *{$fundTitle}* *DITOLAK*.\nAlasan: {$note}\n\nBisa tolong dicek kembali informasinya ya.";
                $this->whatsapp->send_message($pRow['phone'], $wa_msg);
            }
        }

        api_ok(null, ['message' => 'Donation ditolak']);
    }
}
