<?php

defined('BASEPATH') or exit('No direct script access allowed');

class HouseClaims extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();

        $this->load->model('House_claim_model', 'HouseClaimModel');
        $this->load->model('Ownership_model', 'OwnershipModel');
        $this->load->model('Occupancy_model', 'OccupancyModel');
        $this->load->model('House_model', 'HouseModel');
        $this->load->model('User_model', 'UserModel');
        $this->load->library('whatsapp');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $source = trim((string)$this->input->get('source'));
        $status = trim((string)$this->input->get('status'));
        $q      = trim((string)$this->input->get('q'));
        $claim_type = trim((string)$this->input->get('claim_type'));
        $house_id = (int)$this->input->get('house_id');
        $person_id = (int)$this->input->get('person_id');

        $source = $source !== '' ? strtolower($source) : '';
        $status = $status !== '' ? strtolower($status) : '';
        $claim_type = $claim_type !== '' ? strtolower($claim_type) : '';

        if ($source && !in_array($source, ['registration','additional'], true)) {
            api_error('VALIDATION', 'source harus registration|additional', 422);
            return;
        }
        if ($status && !in_array($status, ['pending','approved','rejected'], true)) {
            api_error('VALIDATION', 'status harus pending|approved|rejected', 422);
            return;
        }
        if ($claim_type && !in_array($claim_type, ['owner','tenant'], true)) {
            api_error('VALIDATION', 'claim_type harus owner|tenant', 422);
            return;
        }

        if ($this->has_permission('app.services.requests.unit_claims.review')) {
            $filters = [];
            if ($source) {
                $filters['source'] = $source;
            }
            if ($status) {
                $filters['status'] = $status;
            }
            if ($q !== '') {
                $filters['q'] = $q;
            }
            if ($claim_type) {
                $filters['claim_type'] = $claim_type;
            }
            if ($house_id > 0) {
                $filters['house_id'] = $house_id;
            }
            if ($person_id > 0) {
                $filters['person_id'] = $person_id;
            }

            $res = $this->HouseClaimModel->paginate($page, $per, $filters);
            api_ok(['items' => $res['items']], $res['meta']);
            return;
        }

        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if ($pid <= 0) {
            api_error('FORBIDDEN', 'Akun belum terhubung ke person', 403);
            return;
        }

        $filters = ['person_id' => $pid];
        if ($source) {
            $filters['source'] = $source;
        }
        if ($status) {
            $filters['status'] = $status;
        }
        if ($q !== '') {
            $filters['q'] = $q;
        }
        if ($claim_type) {
            $filters['claim_type'] = $claim_type;
        }

        $res = $this->HouseClaimModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $in = $this->json_input();

        $house_id = (int)($in['house_id'] ?? 0);
        $claim_type = (string)($in['claim_type'] ?? ''); // owner|tenant
        $unit_type = isset($in['unit_type']) ? (string)$in['unit_type'] : null; // house|kavling (optional)
        $is_primary = (int)($in['is_primary'] ?? 0) === 1 ? 1 : 0;
        $note = isset($in['note']) ? (string)$in['note'] : null;

        $source = 'additional';

        $fields = [];
        if ($house_id <= 0) {
            $fields['house_id'] = 'house_id wajib.';
        }
        if (!in_array($claim_type, ['owner','tenant'], true)) {
            $fields['claim_type'] = 'claim_type harus owner|tenant.';
        }
        if ($unit_type !== null && $unit_type !== '' && !in_array($unit_type, ['house','kavling'], true)) {
            $fields['unit_type'] = 'unit_type harus house|kavling.';
        }

        if (!$this->auth_user || empty($this->auth_user['person_id'])) {
            api_error('FORBIDDEN', 'Akun belum terhubung ke person.', 403);
            return;
        }
        $person_id = (int)$this->auth_user['person_id'];

        if ($claim_type === 'tenant' && $is_primary !== 1) {
            $fields['is_primary'] = 'Penghuni kontrak harus memilih tempat tinggal utama.';
        }

        if ($fields) {
            api_validation_error($fields);
            return;
        }

        if ($this->HouseClaimModel->has_open_claim($house_id, $person_id)) {
            api_conflict('Masih ada klaim unit yang pending untuk unit ini.');
            return;
        }

        $hasOcc = (int)$this->db->from('house_occupancies')->where('house_id', $house_id)->where('status', 'active')->count_all_results();
        if ($hasOcc > 0) {
            api_conflict('Unit sudah ditempati / tidak tersedia.');
            return;
        }
        $hasPending = (int)$this->db->from('house_claims')->where('house_id', $house_id)->where('status', 'pending')->count_all_results();
        if ($hasPending > 0) {
            api_conflict('Unit sedang dalam proses klaim (menunggu persetujuan).');
            return;
        }

        $id = $this->HouseClaimModel->create([
            'house_id' => $house_id,
            'person_id' => $person_id,
            'claim_type' => $claim_type,
            'unit_type' => ($unit_type !== '' ? $unit_type : null),
            'is_primary' => $is_primary,
            'note' => $note,
            'source' => $source, // ✅
        ]);

        $claim = $this->HouseClaimModel->find_by_id($id);
        $houseCode = trim((string)($claim['house_code'] ?? ''));
        if ($houseCode === '') {
            $houseCode = 'Unit';
        }
        $ct = ($claim_type === 'owner') ? 'Pemilik' : 'Penghuni';
        audit_log($this, 'Mengajukan klaim unit', 'Mengajukan klaim ' . $ct . ' untuk "' . $houseCode . '"');

        // Send WA Notification to group pengurus
        $admin_wa = $this->whatsapp->get_group_pengurus();
        if ($admin_wa) {
            $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
            $nama = $person['full_name'] ?? 'Warga';
            $wa_msg = "Assalamu’alaikum\n\nTerdapat pengajuan klaim unit/rumah dengan data:\nNama: *{$nama}*\nUnit: *{$houseCode}*\n\nMohon bantuannya untuk dilakukan pengecekan apabila sudah berkenan.\n\n—\nPesan ini dikirim otomatis melalui layanan SIS Paguyuban";
            $this->whatsapp->send_message($admin_wa, $wa_msg);
        }

        api_ok($this->HouseClaimModel->find_by_id($id), null, 201);
    }

    public function approve(int $id = 0): void
    {
        $this->require_any_permission(['app.services.requests.unit_claims.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $claim = $this->HouseClaimModel->find_by_id($id);
        if (!$claim) {
            api_not_found();
            return;
        }
        if (($claim['status'] ?? '') !== 'pending') {
            api_conflict('Klaim tidak dalam status pending.');
            return;
        }

        $house_id = (int)$claim['house_id'];
        $person_id = (int)$claim['person_id'];
        $claim_type = (string)$claim['claim_type']; // owner|tenant
        $unit_type = isset($claim['unit_type']) ? (string)$claim['unit_type'] : null; // house|kavling
        $is_primary = (int)($claim['is_primary'] ?? 0) === 1 ? 1 : 0;

        if ($unit_type && in_array($unit_type, ['house','kavling'], true)) {
            $this->HouseModel->update_status_type($house_id, $unit_type, null);
        }

        $hhid = $this->UserModel->resolve_household_id_by_person($person_id);

        if ($claim_type === 'owner') {
            $this->OwnershipModel->end_active_by_house($house_id);
            $this->OwnershipModel->create([
                'house_id' => $house_id,
                'person_id' => $person_id,
                'start_date' => date('Y-m-d'),
                'note' => 'Created from approved claim',
            ]);

            if ($is_primary === 1 && $hhid) {
                $this->db
                    ->where('household_id', $hhid)
                    ->where('status', 'active')
                    ->where_in('occupancy_type', ['owner_live','tenant'])
                    ->update('house_occupancies', [
                        'status' => 'ended',
                        'end_date' => date('Y-m-d'),
                    ]);

                $this->OccupancyModel->end_active_live_by_house($house_id);
                $this->OccupancyModel->create([
                    'house_id' => $house_id,
                    'household_id' => $hhid,
                    'occupancy_type' => 'owner_live',
                    'start_date' => date('Y-m-d'),
                    'status' => 'active',
                    'note' => 'Created from approved claim',
                ]);
                $this->HouseModel->update_status_type($house_id, null, 'occupied');
            } else {
                $this->HouseModel->update_status_type($house_id, null, 'owned');
            }
        }

        if ($claim_type === 'tenant') {
            if (!$hhid) {
                api_error('VALIDATION', 'Household untuk pengklaim tidak ditemukan.', 422);
                return;
            }

            $this->db
                ->where('household_id', $hhid)
                ->where('status', 'active')
                ->where_in('occupancy_type', ['owner_live','tenant'])
                ->update('house_occupancies', [
                    'status' => 'ended',
                    'end_date' => date('Y-m-d'),
                ]);

            $hasOcc = (int)$this->db->from('house_occupancies')->where('house_id', $house_id)->where('status', 'active')->count_all_results();
            if ($hasOcc > 0) {
                api_conflict('Unit sudah ditempati.');
                return;
            }

            if (!$unit_type) {
                $this->HouseModel->update_status_type($house_id, 'house', null);
            }

            $this->OccupancyModel->end_active_live_by_house($house_id);
            $this->OccupancyModel->create([
                'house_id' => $house_id,
                'household_id' => $hhid,
                'occupancy_type' => 'tenant',
                'start_date' => date('Y-m-d'),
                'status' => 'active',
                'note' => 'Created from approved claim',
            ]);
            $this->HouseModel->update_status_type($house_id, null, 'rented');
        }

        $this->HouseClaimModel->review($id, 'approved', (int)$this->auth_user['id'], 'Approved');

        $houseCode = trim((string)($claim['house_code'] ?? ''));
        if ($houseCode === '') {
            $houseCode = 'Unit';
        }
        $personName = trim((string)($claim['person_name'] ?? ''));
        if ($personName === '') {
            $personName = 'warga';
        }
        audit_log($this, 'Menyetujui klaim unit', 'Menyetujui klaim unit "' . $houseCode . '" untuk ' . $personName);

        // Send WA Notification
        $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
        if ($person && !empty($person['phone'])) {
            $nama = $person['full_name'] ?? 'Warga';
            $wa_msg = "Assalamu’alaikum, {$nama}\n\nAlhamdulillah, pengajuan klaim unit/rumah *{$houseCode}* Anda telah disetujui.\nSilakan mulai menggunakan layanan yang tersedia.\n\nSemoga dapat membantu memudahkan urusan bersama di lingkungan kita.\n\n—\nPesan ini dikirim otomatis melalui layanan SIS Paguyuban";
            $this->whatsapp->send_message($person['phone'], $wa_msg);
        }

        api_ok($this->HouseClaimModel->find_by_id($id));
    }

    public function reject(int $id = 0): void
    {
        $this->require_any_permission(['app.services.requests.unit_claims.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $claim = $this->HouseClaimModel->find_by_id($id);
        if (!$claim) {
            api_not_found();
            return;
        }
        if (($claim['status'] ?? '') !== 'pending') {
            api_conflict('Klaim tidak dalam status pending.');
            return;
        }

        $in = $this->json_input();
        $reject_note = isset($in['reject_note']) ? (string)$in['reject_note'] : null;

        $this->HouseClaimModel->review($id, 'rejected', (int)$this->auth_user['id'], null, $reject_note);

        $houseCode = trim((string)($claim['house_code'] ?? ''));
        if ($houseCode === '') {
            $houseCode = 'Unit';
        }
        $personName = trim((string)($claim['person_name'] ?? ''));
        if ($personName === '') {
            $personName = 'warga';
        }
        audit_log($this, 'Menolak klaim unit', 'Menolak klaim unit "' . $houseCode . '" untuk ' . $personName);

        // Send WA Notification
        $person_id = (int)$claim['person_id'];
        $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
        if ($person && !empty($person['phone'])) {
            $nama = $person['full_name'] ?? 'Warga';
            $wa_msg = "Assalamu’alaikum, {$nama}\n\nTerima kasih atas pengajuan klaim unit/rumah *{$houseCode}* yang telah disampaikan.\nUntuk saat ini, pengajuan tersebut belum dapat diproses dengan alasan berikut:\n\n{$reject_note}\n\nSilakan dikomunikasikan kembali dengan pengurus apabila diperlukan.\nInsyaAllah akan dibantu.\n\n—\nPesan ini dikirim otomatis melalui layanan SIS Paguyuban";
            $this->whatsapp->send_message($person['phone'], $wa_msg);
        }

        api_ok($this->HouseClaimModel->find_by_id($id));
    }
}
