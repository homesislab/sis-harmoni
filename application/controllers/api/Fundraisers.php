<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $is_admin = in_array('admin', $this->auth_roles, true);

        $filters = [
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];

        $filters['status'] = $is_admin
            ? ($this->input->get('status') ? (string)$this->input->get('status') : null)
            : 'active';

        $res = $this->FundraiserModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_role(['admin']);

        $in = $this->json_input();
        $err = $this->FundraiserModel->validate_payload($in, true);
        if ($err) { api_validation_error($err); return; }

        $id = $this->FundraiserModel->create($in);

        audit_log($this, 'fundraiser_create', 'Create fundraiser #' . $id);

        api_ok($this->FundraiserModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        if ($id <= 0) { api_not_found(); return; }
        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }
        api_ok($row);
    }

    public function update(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $in = $this->json_input();
        $err = $this->FundraiserModel->validate_payload($in, false);
        if ($err) { api_validation_error($err); return; }

        $this->FundraiserModel->update($id, $in);

        audit_log($this, 'fundraiser_update', 'Update fundraiser #' . $id);

        api_ok($this->FundraiserModel->find_by_id($id));
    }

    public function destroy(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $approved = $this->DonationModel->count_approved_for_fundraiser($id);
        if ($approved > 0) {
            api_error('FORBIDDEN', 'Fundraiser tidak bisa dihapus karena sudah ada donasi approved', 403);
            return;
        }

        $this->FundraiserModel->delete($id);

        audit_log($this, 'fundraiser_delete', 'Delete fundraiser #' . $id);

        api_ok(null, ['message' => 'Fundraiser dihapus']);
    }

    public function close(int $id = 0): void
    {
        $this->require_role(['admin']);
        if ($id <= 0) { api_not_found(); return; }

        $row = $this->FundraiserModel->find_by_id($id);
        if (!$row) { api_not_found(); return; }

        $this->FundraiserModel->set_status($id, 'closed');

        audit_log($this, 'fundraiser_close', 'Close fundraiser #' . $id);

        api_ok(null, ['message' => 'Fundraiser ditutup']);
    }

    public function donate(int $fundraiser_id = 0): void
    {
        if ($fundraiser_id <= 0) { api_not_found(); return; }

        $fund = $this->FundraiserModel->find_by_id($fundraiser_id);
        if (!$fund) { api_not_found('Fundraiser tidak ditemukan'); return; }
        if ($fund['status'] !== 'active') { api_error('FORBIDDEN','Fundraiser sudah ditutup',403); return; }

        $in = $this->json_input();

        $amount = (float)($in['amount'] ?? 0);
        $paid_at = trim((string)($in['paid_at'] ?? ''));
        $proof = isset($in['proof_file_url']) ? trim((string)$in['proof_file_url']) : null;
        $note  = isset($in['note']) ? trim((string)$in['note']) : null;
        $is_anonymous = !empty($in['is_anonymous']) ? 1 : 0;

        $is_admin = in_array('admin', $this->auth_roles, true);

        $person_id = null;
        if ($is_admin && isset($in['person_id'])) $person_id = (int)$in['person_id'];
        if (!$person_id) $person_id = !empty($this->auth_user['person_id']) ? (int)$this->auth_user['person_id'] : 0;

        $err = [];
        if ($person_id <= 0) $err['person_id'] = 'Akun belum terhubung ke data warga (person_id)';
        if ($amount <= 0) $err['amount'] = 'Harus > 0';
        if ($paid_at === '' || !preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', $paid_at)) $err['paid_at'] = 'Format YYYY-MM-DD HH:MM:SS';

        $proof_err = validate_proof_url($this, $proof);
        if ($proof_err) $err['proof_file_url'] = $proof_err;

        if ($err) { api_validation_error($err); return; }

        $id = $this->DonationModel->create([
            'fundraiser_id' => $fundraiser_id,
            'person_id' => $person_id,
            'amount' => $amount,
            'paid_at' => $paid_at,
            'proof_file_url' => $proof,
            'note' => $note,
            'is_anonymous' => $is_anonymous,
        ]);

        audit_log($this, 'donation_create', "Create donation #$id fundraiser #$fundraiser_id");

        api_ok($this->DonationModel->find_by_id($id), null, 201);
    }

    public function donations(int $fundraiser_id = 0): void
    {
        if ($fundraiser_id <= 0) { api_not_found(); return; }

        $fund = $this->FundraiserModel->find_by_id($fundraiser_id);
        if (!$fund) { api_not_found('Fundraiser tidak ditemukan'); return; }

        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)$this->input->get('per_page') ?: 20));

        $is_admin = in_array('admin', $this->auth_roles, true);
        $status = $this->input->get('status') ? (string)$this->input->get('status') : null;

        if (!$is_admin) {
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
