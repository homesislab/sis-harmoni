<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Fundraisers extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('Fundraiser_model', 'FundraiserModel');
        $this->load->model('Donation_model', 'DonationModel');
        $this->load->model('Fundraiser_update_model', 'UpdateModel');
        $this->load->model('Ledger_model', 'LedgerModel');
        $this->load->library('whatsapp');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $can_manage = $this->has_permission('app.services.finance.donation_campaigns.manage');

        $filters = [
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        $filters['status'] = $can_manage
            ? ($this->input->get('status') ? (string)$this->input->get('status') : null)
            : 'active';

        $res = $this->FundraiserModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_permission('app.services.finance.donation_campaigns.manage');

        $in = $this->json_input();
        $err = $this->FundraiserModel->validate_payload($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->FundraiserModel->create($in);

        $title = trim((string)($in['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Membuat program donasi', 'Membuat program donasi "' . $title . '"');

        api_ok($this->FundraiserModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }
        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_permission('app.services.finance.donation_campaigns.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $err = $this->FundraiserModel->validate_payload($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $this->FundraiserModel->update($id, $in);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Memperbarui program donasi', 'Memperbarui program donasi "' . $title . '"');

        api_ok($this->FundraiserModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_permission('app.services.finance.donation_campaigns.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $approved = $this->DonationModel->count_approved_for_fundraiser($id);
        if ($approved > 0) {
            api_error('FORBIDDEN', 'Fundraiser tidak bisa dihapus karena sudah ada donasi approved', 403);
            return;
        }

        $this->FundraiserModel->delete($id);

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Menghapus program donasi', 'Menghapus program donasi "' . $title . '"');

        api_ok(null, ['message' => 'Fundraiser dihapus']);
    }

    public function close(int $id = 0): void
    {
        $this->require_permission('app.services.finance.donation_campaigns.manage');
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->FundraiserModel->set_status($id, 'closed');

        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            $title = 'Tanpa judul';
        }
        audit_log($this, 'Menutup program donasi', 'Menutup program donasi "' . $title . '"');

        api_ok(null, ['message' => 'Fundraiser ditutup']);
    }

    public function donate(int $fundraiser_id = 0): void
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
        if ($fund['status'] !== 'active') {
            api_error('FORBIDDEN', 'Fundraiser sudah ditutup', 403);
            return;
        }

        $in = $this->json_input();

        $amount = (float)($in['amount'] ?? 0);
        $paid_at = trim((string)($in['paid_at'] ?? ''));
        $proof = isset($in['proof_file_url']) ? trim((string)$in['proof_file_url']) : null;
        $note  = isset($in['note']) ? trim((string)$in['note']) : null;
        $is_anonymous = !empty($in['is_anonymous']) ? 1 : 0;

        $can_manage = $this->has_permission('app.services.finance.donation_campaigns.manage');

        $person_id = null;
        if ($can_manage && isset($in['person_id'])) {
            $person_id = (int)$in['person_id'];
        }
        if (!$person_id) {
            $person_id = !empty($this->auth_user['person_id']) ? (int)$this->auth_user['person_id'] : 0;
        }

        $err = [];
        if ($person_id <= 0) {
            $err['person_id'] = 'Akun belum terhubung ke data warga (person_id)';
        }
        if ($amount <= 0) {
            $err['amount'] = 'Harus > 0';
        }
        if ($paid_at === '' || !preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', $paid_at)) {
            $err['paid_at'] = 'Format YYYY-MM-DD HH:MM:SS';
        }

        $proof_err = validate_proof_url($this, $proof);
        if ($proof_err) {
            $err['proof_file_url'] = $proof_err;
        }

        if ($err) {
            api_validation_error($err);
            return;
        }

        $id = $this->DonationModel->create([
            'fundraiser_id' => $fundraiser_id,
            'person_id' => $person_id,
            'amount' => $amount,
            'paid_at' => $paid_at,
            'proof_file_url' => $proof,
            'note' => $note,
            'is_anonymous' => $is_anonymous,
        ]);

        $don = $this->DonationModel->find_by_id($id);
        $fundTitle = trim((string)($fund['title'] ?? ($don['fundraiser_title'] ?? '')));
        if ($fundTitle === '') {
            $fundTitle = 'Program donasi';
        }
        $amt = number_format((float)($don['amount'] ?? $amount), 0, ',', '.');
        audit_log($this, 'Mengirim donasi', 'Mengirim donasi Rp ' . $amt . ' untuk "' . $fundTitle . '"');

        // Send WA Notification
        $admin_wa = $this->whatsapp->get_admin_keuangan();
        if ($admin_wa) {
            $donor = ($is_anonymous === 1) ? 'Anonim' : 'Warga';
            if ($is_anonymous === 0 && $person_id > 0) {
                $pRow = $this->db->get_where('persons', ['id' => $person_id])->row_array();
                if ($pRow) {
                    $donor = $pRow['full_name'];
                }
            }
            $wa_msg = "*[Info SIS]*\n\nAssalamu'alaikum, Admin Keuangan.\n\n📢 *Ada Donasi Baru!*\n\nKonfirmasi donasi sebesar *Rp {$amt}* dari *{$donor}* untuk program *{$fundTitle}*.\n\nMohon bantuannya untuk verifikasi bukti transfer di aplikasi.";
            $this->whatsapp->send_message($admin_wa, $wa_msg);
        }

        api_ok($this->DonationModel->find_by_id($id), null, 201);
    }

    public function donations(int $fundraiser_id = 0): void
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

        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $can_manage = $this->has_permission('app.services.finance.donation_campaigns.manage');
        $status = $this->input->get('status') ? (string)$this->input->get('status') : null;

        if (!$can_manage) {
            $status = 'approved';
        } else {
            if ($status && !in_array($status, ['pending','approved','rejected'], true)) {
                api_validation_error(['status' => 'Nilai tidak valid']);
                return;
            }
        }

        $res = $this->DonationModel->paginate_for_fundraiser($fundraiser_id, $status, $page, $per);
        api_ok(['items' => $res['items']], $res['meta']);
    }

}
