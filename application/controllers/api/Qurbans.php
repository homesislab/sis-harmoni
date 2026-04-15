<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Qurbans extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        if ($this->router->fetch_method() !== 'active') {
            $this->require_auth();
        } else {
            $this->optional_auth();
        }
        $this->load->model('Qurban_model', 'QurbanModel');
        $this->load->model('Ledger_model', 'LedgerModel');
    }

    public function index(): void
    {
        $canManage = $this->has_permission('app.services.finance.qurban.manage');
        $status = $canManage ? trim((string)$this->input->get('status')) : 'active';
        $periods = $this->QurbanModel->periods(['status' => $status ?: null]);

        foreach ($periods as &$period) {
            $period['animals'] = $this->QurbanModel->animals((int)$period['id'], !$canManage);
        }

        api_ok(['items' => $periods]);
    }

    public function active(): void
    {
        $period = $this->QurbanModel->active_period();
        if (!$period) {
            api_ok(['period' => null, 'animals' => [], 'my_participations' => []]);
            return;
        }

        $personId = (int)($this->auth_user['person_id'] ?? 0);
        api_ok([
            'period' => $period,
            'animals' => $this->QurbanModel->animals((int)$period['id'], true),
            'my_participations' => $personId > 0 ? $this->QurbanModel->my_participations($personId, (int)$period['id']) : [],
            'participants' => $this->QurbanModel->public_participations((int)$period['id']),
        ]);
    }

    public function store_period(): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $in = $this->json_input();
        $err = $this->validate_period($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }

        $account = $this->LedgerModel->find_account((int)$in['ledger_account_id']);
        $this->require_org_access($account['type'] ?? null);

        $in['created_by'] = (int)$this->auth_user['id'];
        $id = $this->QurbanModel->create_period($in);
        audit_log($this, 'Membuat periode qurban', 'Membuat periode qurban "' . trim((string)$in['name']) . '"');
        api_ok($this->QurbanModel->period($id), null, 201);
    }

    public function update_period(int $id = 0): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $period = $this->QurbanModel->period($id);
        if (!$period) {
            api_not_found();
            return;
        }
        $this->require_org_access($period['ledger_account_type'] ?? null);

        $in = $this->json_input();
        $err = $this->validate_period($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }
        if (!empty($in['ledger_account_id'])) {
            $account = $this->LedgerModel->find_account((int)$in['ledger_account_id']);
            $this->require_org_access($account['type'] ?? null);
        }

        $this->QurbanModel->update_period($id, $in);
        api_ok($this->QurbanModel->period($id));
    }

    public function store_animal(): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $in = $this->json_input();
        $err = $this->validate_animal($in, true);
        if ($err) {
            api_validation_error($err);
            return;
        }
        $period = $this->QurbanModel->period((int)$in['period_id']);
        if (!$period) {
            api_not_found('Periode qurban tidak ditemukan');
            return;
        }
        $this->require_org_access($period['ledger_account_type'] ?? null);

        $id = $this->QurbanModel->create_animal($in);
        audit_log($this, 'Menambah hewan qurban', 'Menambah hewan qurban "' . trim((string)$in['name']) . '"');
        api_ok($this->QurbanModel->animal($id), null, 201);
    }

    public function update_animal(int $id = 0): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $animal = $this->QurbanModel->animal($id);
        if (!$animal) {
            api_not_found();
            return;
        }
        $period = $this->QurbanModel->period((int)$animal['period_id']);
        $this->require_org_access($period['ledger_account_type'] ?? null);

        $in = $this->json_input();
        $err = $this->validate_animal($in, false);
        if ($err) {
            api_validation_error($err);
            return;
        }
        $this->QurbanModel->update_animal($id, $in);
        $this->QurbanModel->refresh_animal_counts($id);
        api_ok($this->QurbanModel->animal($id));
    }

    public function participate(int $animal_id = 0): void
    {
        $animal = $this->QurbanModel->animal($animal_id);
        if (!$animal) {
            api_not_found('Hewan qurban tidak ditemukan');
            return;
        }
        $animal = $this->QurbanModel->resolve_animal_for_participation($animal);
        if (!$animal) {
            api_error('CONFLICT', 'Kuota hewan qurban sudah penuh atau ditutup', 409);
            return;
        }

        $period = $this->QurbanModel->period((int)$animal['period_id']);
        if (!$period || ($period['status'] ?? '') !== 'active') {
            api_error('FORBIDDEN', 'Periode qurban belum aktif', 403);
            return;
        }

        $personId = (int)($this->auth_user['person_id'] ?? 0);
        if ($personId <= 0) {
            api_error('FORBIDDEN', 'Akun belum terhubung ke data warga', 403);
            return;
        }

        $in = $this->json_input();
        $name = trim((string)($in['participant_name'] ?? ''));
        if ($name === '') {
            $row = $this->db->get_where('persons', ['id' => $personId])->row_array();
            $name = trim((string)($row['full_name'] ?? ''));
        }
        if ($name === '') {
            api_validation_error(['participant_name' => 'Nama peserta wajib diisi']);
            return;
        }

        $id = $this->QurbanModel->create_participation([
            'period_id' => (int)$period['id'],
            'animal_id' => (int)$animal['id'],
            'person_id' => $personId,
            'participant_name' => $name,
            'amount' => (float)$animal['price'],
            'group_no' => $this->QurbanModel->next_group_no($animal),
            'self_brought_animal_type' => isset($in['self_brought_animal_type']) ? trim((string)$in['self_brought_animal_type']) : null,
            'self_brought_weight_kg' => $in['self_brought_weight_kg'] ?? null,
            'self_brought_notes' => isset($in['self_brought_notes']) ? trim((string)$in['self_brought_notes']) : null,
            'note' => isset($in['note']) ? trim((string)$in['note']) : null,
        ]);
        audit_log($this, 'Mendaftar qurban', 'Mendaftar qurban "' . $name . '"');
        api_ok($this->QurbanModel->participation($id), null, 201);
    }

    public function manual_participation(int $animal_id = 0): void
    {
        $this->require_permission('app.services.finance.qurban.manage');

        $animal = $this->QurbanModel->animal($animal_id);
        if (!$animal) {
            api_not_found('Hewan qurban tidak ditemukan');
            return;
        }
        $animal = $this->QurbanModel->resolve_animal_for_participation($animal);
        if (!$animal) {
            api_error('CONFLICT', 'Kuota hewan qurban sudah penuh atau ditutup', 409);
            return;
        }

        $period = $this->QurbanModel->period((int)$animal['period_id']);
        if (!$period) {
            api_not_found('Periode qurban tidak ditemukan');
            return;
        }
        $this->require_org_access($period['ledger_account_type'] ?? null);

        $in = $this->json_input();
        $name = trim((string)($in['participant_name'] ?? ''));
        if ($name === '') {
            api_validation_error(['participant_name' => 'Nama peserta wajib diisi']);
            return;
        }

        $personId = (int)($in['person_id'] ?? 0);
        if ($personId > 0 && !$this->db->get_where('persons', ['id' => $personId])->row_array()) {
            api_validation_error(['person_id' => 'Warga tidak ditemukan']);
            return;
        }

        $id = $this->QurbanModel->create_participation([
            'period_id' => (int)$period['id'],
            'animal_id' => (int)$animal['id'],
            'person_id' => $personId > 0 ? $personId : null,
            'participant_name' => $name,
            'amount' => (float)$animal['price'],
            'group_no' => $this->QurbanModel->next_group_no($animal),
            'self_brought_animal_type' => isset($in['self_brought_animal_type']) ? trim((string)$in['self_brought_animal_type']) : null,
            'self_brought_weight_kg' => $in['self_brought_weight_kg'] ?? null,
            'self_brought_notes' => isset($in['self_brought_notes']) ? trim((string)$in['self_brought_notes']) : null,
            'participant_source' => isset($in['participant_source']) ? trim((string)$in['participant_source']) : ($personId > 0 ? 'resident' : 'manual'),
            'manual_contact' => isset($in['manual_contact']) ? trim((string)$in['manual_contact']) : null,
            'note' => isset($in['note']) ? trim((string)$in['note']) : null,
            'created_by' => (int)$this->auth_user['id'],
        ]);
        audit_log($this, 'Input peserta qurban manual', 'Mencatat peserta qurban "' . $name . '"');
        api_ok($this->QurbanModel->participation($id), null, 201);
    }

    public function cancel_participation(int $id = 0): void
    {
        $row = $this->QurbanModel->participation($id);
        if (!$row) {
            api_not_found();
            return;
        }
        if (($row['status'] ?? '') === 'paid') {
            api_conflict('Peserta yang sudah lunas tidak bisa dibatalkan');
            return;
        }

        $canManage = $this->has_permission('app.services.finance.qurban.manage');
        $personId = (int)($this->auth_user['person_id'] ?? 0);
        if (!$canManage && ((int)$row['person_id'] !== $personId || $personId <= 0)) {
            api_error('FORBIDDEN', 'Data qurban ini bukan milik akun kamu', 403);
            return;
        }

        if ($canManage) {
            $period = $this->QurbanModel->period((int)$row['period_id']);
            $this->require_org_access($period['ledger_account_type'] ?? null);
        }

        $this->QurbanModel->cancel_participation($id);
        audit_log($this, 'Membatalkan peserta qurban', 'Membatalkan peserta qurban "' . ($row['participant_name'] ?? 'Peserta') . '"');
        api_ok($this->QurbanModel->participation($id));
    }

    public function update_participation(int $id = 0): void
    {
        $row = $this->QurbanModel->participation($id);
        if (!$row) {
            api_not_found();
            return;
        }
        if (($row['status'] ?? '') === 'cancelled') {
            api_conflict('Peserta yang sudah dibatalkan tidak bisa diubah');
            return;
        }

        $canManage = $this->has_permission('app.services.finance.qurban.manage');
        $personId = (int)($this->auth_user['person_id'] ?? 0);
        if (!$canManage && ((int)$row['person_id'] !== $personId || $personId <= 0)) {
            api_error('FORBIDDEN', 'Data qurban ini bukan milik akun kamu', 403);
            return;
        }

        if ($canManage) {
            $period = $this->QurbanModel->period((int)$row['period_id']);
            $this->require_org_access($period['ledger_account_type'] ?? null);
        }

        $in = $this->json_input();
        $name = trim((string)($in['participant_name'] ?? ''));
        if ($name === '') {
            api_validation_error(['participant_name' => 'Nama peserta wajib diisi']);
            return;
        }

        $payload = ['participant_name' => $name];
        if ($canManage && array_key_exists('manual_contact', $in)) {
            $payload['manual_contact'] = trim((string)$in['manual_contact']);
        }
        if (array_key_exists('note', $in)) {
            $payload['note'] = trim((string)$in['note']);
        }

        $this->QurbanModel->update_participation($id, $payload);
        audit_log($this, 'Mengubah peserta qurban', 'Mengubah peserta qurban "' . $name . '"');
        api_ok($this->QurbanModel->participation($id));
    }

    public function submit_payment(int $id = 0): void
    {
        $row = $this->QurbanModel->participation($id);
        if (!$row) {
            api_not_found();
            return;
        }
        $personId = (int)($this->auth_user['person_id'] ?? 0);
        if ((int)$row['person_id'] !== $personId) {
            api_error('FORBIDDEN', 'Data qurban ini bukan milik akun kamu', 403);
            return;
        }
        if (($row['status'] ?? '') === 'paid') {
            api_conflict('Pembayaran sudah dikunci');
            return;
        }

        $in = $this->json_input();
        $paidAt = trim((string)($in['paid_at'] ?? ''));
        $proof = trim((string)($in['proof_file_url'] ?? ''));
        $err = [];
        if ($paidAt === '' || !preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', $paidAt)) {
            $err['paid_at'] = 'Format YYYY-MM-DD HH:MM:SS';
        }
        $proofErr = validate_proof_url($this, $proof);
        if ($proofErr) {
            $err['proof_file_url'] = $proofErr;
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        $this->QurbanModel->mark_payment_submitted($id, [
            'paid_at' => $paidAt,
            'proof_file_url' => $proof,
            'note' => isset($in['note']) ? trim((string)$in['note']) : null,
        ]);
        audit_log($this, 'Konfirmasi pembayaran qurban', 'Mengirim bukti pembayaran qurban');
        api_ok($this->QurbanModel->participation($id));
    }

    public function participations(): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $filters = [
            'period_id' => $this->input->get('period_id') ? (int)$this->input->get('period_id') : null,
            'status' => $this->input->get('status') ? trim((string)$this->input->get('status')) : null,
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
        ];
        api_ok(['items' => $this->QurbanModel->participations($filters)]);
    }

    public function approve_payment(int $id = 0): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $row = $this->QurbanModel->participation($id);
        if (!$row) {
            api_not_found();
            return;
        }
        if (($row['status'] ?? '') === 'paid') {
            api_conflict('Pembayaran sudah disetujui');
            return;
        }
        if (!in_array(($row['status'] ?? ''), ['joined', 'pending_payment', 'payment_rejected'], true)) {
            api_error('CONFLICT', 'Status peserta belum bisa ditandai lunas', 409);
            return;
        }

        $period = $this->QurbanModel->period((int)$row['period_id']);
        $this->require_org_access($period['ledger_account_type'] ?? null);

        $this->db->trans_begin();
        try {
            $ledgerId = (int)($period['ledger_account_id'] ?? 0);
            if ($ledgerId <= 0) {
                $ledgerId = $this->LedgerModel->ensure_default_account('dkm');
            }
            $paidAt = !empty($row['paid_at']) ? $row['paid_at'] : date('Y-m-d H:i:s');
            $entryId = $this->LedgerModel->create_entry([
                'ledger_account_id' => $ledgerId,
                'direction' => 'in',
                'amount' => (float)$row['amount'],
                'category' => 'Qurban',
                'description' => 'Qurban ' . ($row['period_name'] ?? '') . ' - ' . ($row['participant_name'] ?? 'Warga'),
                'occurred_at' => $paidAt,
                'source_type' => 'qurban_participation',
                'source_id' => (int)$row['id'],
                'created_by' => (int)$this->auth_user['id'],
            ]);
            $this->QurbanModel->approve_payment($id, (int)$this->auth_user['id'], $entryId, $paidAt);
            $this->db->trans_commit();
            audit_log($this, 'Menandai pembayaran qurban lunas', 'Menandai pembayaran qurban lunas "' . ($row['participant_name'] ?? 'Warga') . '"');
            api_ok($this->QurbanModel->participation($id), ['ledger_entry_id' => $entryId]);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'approve_qurban_payment error: ' . $e->getMessage());
            api_error('SERVER_ERROR', 'Terjadi kesalahan pada server', 500);
        }
    }

    public function reject_payment(int $id = 0): void
    {
        $this->require_permission('app.services.finance.qurban.manage');
        $row = $this->QurbanModel->participation($id);
        if (!$row) {
            api_not_found();
            return;
        }
        if (($row['status'] ?? '') === 'paid') {
            api_conflict('Pembayaran sudah dikunci');
            return;
        }
        $in = $this->json_input();
        $this->QurbanModel->reject_payment($id, (int)$this->auth_user['id'], isset($in['note']) ? trim((string)$in['note']) : null);
        api_ok($this->QurbanModel->participation($id));
    }

    private function validate_period(array $in, bool $create): array
    {
        $err = [];
        foreach (['year', 'name', 'ledger_account_id'] as $field) {
            if ($create && (!isset($in[$field]) || trim((string)$in[$field]) === '')) {
                $err[$field] = 'Wajib diisi';
            }
        }
        if (isset($in['year']) && ((int)$in['year'] < 2000 || (int)$in['year'] > 2100)) {
            $err['year'] = 'Tahun tidak valid';
        }
        if (isset($in['status']) && !in_array($in['status'], ['draft', 'active', 'closed'], true)) {
            $err['status'] = 'Nilai tidak valid';
        }
        if (isset($in['ledger_account_id']) && !$this->LedgerModel->find_account((int)$in['ledger_account_id'])) {
            $err['ledger_account_id'] = 'Akun kas tidak ditemukan';
        }
        return $err;
    }

    private function validate_animal(array $in, bool $create): array
    {
        $err = [];
        foreach (['period_id', 'name', 'price', 'quota'] as $field) {
            if ($create && (!isset($in[$field]) || trim((string)$in[$field]) === '')) {
                $err[$field] = 'Wajib diisi';
            }
        }
        if (isset($in['package_type']) && !in_array($in['package_type'], ['managed', 'self_brought'], true)) {
            $err['package_type'] = 'Nilai tidak valid';
        }
        if (isset($in['animal_type']) && !in_array($in['animal_type'], ['cow', 'goat', 'sheep'], true)) {
            $err['animal_type'] = 'Nilai tidak valid';
        }
        if (isset($in['status']) && !in_array($in['status'], ['available', 'full', 'closed'], true)) {
            $err['status'] = 'Nilai tidak valid';
        }
        if (isset($in['price']) && (float)$in['price'] < 0) {
            $err['price'] = 'Tidak boleh negatif';
        }
        if (isset($in['quota']) && (int)$in['quota'] <= 0) {
            $err['quota'] = 'Harus lebih dari 0';
        }
        return $err;
    }
}
