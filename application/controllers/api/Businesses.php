<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Businesses extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Local_business_model', 'BusinessModel');
        $this->load->model('Local_product_model', 'ProductModel');
        $this->load->library('whatsapp');
    }

    public function index(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $pid = (int)($this->auth_user['person_id'] ?? 0);

        $can_review = $this->has_permission('app.services.requests.businesses.review');

        $owner_q_raw = $this->input->get('owner_person_id');
        $owner_q = ($owner_q_raw !== null && $owner_q_raw !== '') ? (int)$owner_q_raw : null;

        $filters = [
            'q' => $this->input->get('q') ? trim((string)$this->input->get('q')) : null,
            'category' => $this->input->get('category') ? (string)$this->input->get('category') : null,
            'owner_person_id' => $owner_q, // may be null
        ];

        $is_lapak_q = $this->input->get('is_lapak');
        if ($is_lapak_q !== null && $is_lapak_q !== '') {
            $filters['is_lapak'] = (int)$is_lapak_q;
        } else {
            $filters['is_lapak'] = null;
        }

        if ($can_review) {
            $filters['status'] = $this->input->get('status') ? (string)$this->input->get('status') : null;
        } else {
            $is_my = ($owner_q !== null && $owner_q === $pid && $pid > 0);

            if ($is_my) {
                $filters['owner_person_id'] = $pid;
                $filters['status'] = $this->input->get('status') ? (string)$this->input->get('status') : null; // null = all
            } else {
                $filters['owner_person_id'] = null; // ignore foreign owner filter
                $filters['status'] = 'active';
            }
        }

        $res = $this->BusinessModel->paginate($page, $per, $filters);
        api_ok(['items' => $res['items']], $res['meta']);
    }

    public function store(): void
    {
        $this->require_any_permission(['app.profile.family.umkm', 'app.services.requests.businesses.review']);
        $in = $this->json_input();
        $err = [];
        if (empty($in['name'])) {
            $err['name'] = 'Wajib diisi';
        }
        if (empty($in['category'])) {
            $err['category'] = 'Wajib diisi';
        }
        if ($err) {
            api_validation_error($err);
            return;
        }

        $payload = $in;
        $payload['created_by'] = (int)$this->auth_user['id'];
        $payload['owner_person_id'] = (int)($this->auth_user['person_id'] ?? 0) ?: null;
        if (empty($payload['house_id']) && !empty($this->auth_house_id)) {
            $payload['house_id'] = (int)$this->auth_house_id;
        }
        if (!$this->has_permission('app.services.requests.businesses.review')) {
            $payload['status'] = 'pending';
        }

        $id = $this->BusinessModel->create($payload);

        // Send WA Notification to group pengurus
        $admin_wa = $this->whatsapp->get_group_pengurus();
        if ($admin_wa) {
            $person_id = $payload['owner_person_id'] ?? 0;
            $nama = 'Warga';
            if ($person_id) {
                $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
                if ($person) {
                    $nama = $person['full_name'];
                }
            }
            $bizName = $payload['name'] ?? 'Lapak/UMKM';
            $wa_msg = "Assalamu’alaikum\n\nTerdapat pengajuan UMKM/Lapak baru dengan data:\nNama Pemilik: *{$nama}*\nNama Lapak: *{$bizName}*\n\nMohon bantuannya untuk dilakukan pengecekan apabila sudah berkenan.\n\n—\nPesan ini dikirim otomatis melalui layanan SIS Paguyuban";
            $this->whatsapp->send_message($admin_wa, $wa_msg);
        }

        api_ok($this->BusinessModel->find_by_id($id), null, 201);
    }

    public function show(int $id = 0): void
    {
        $this->require_any_permission(['app.services.resident.umkm.view', 'app.profile.family.umkm', 'app.services.requests.businesses.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }

        $biz = $this->BusinessModel->find_by_id($id);
        if (!$biz) {
            api_not_found();
            return;
        }

        $can_review = $this->has_permission('app.services.requests.businesses.review');
        $pid = (int)($this->auth_user['person_id'] ?? 0);

        if (!$can_review) {
            $is_owner = ((int)($biz['owner_person_id'] ?? 0) === $pid);
            $is_active = (($biz['status'] ?? '') === 'active');
            if (!$is_active && !$is_owner) {
                api_not_found();
                return;
            }
        }

        api_ok($biz);
    }

    public function update(int $id = 0): void
    {
        $this->require_any_permission(['app.profile.family.umkm', 'app.services.requests.businesses.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $can_review = $this->has_permission('app.services.requests.businesses.review');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if (!$can_review && (int)($row['owner_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        $in = $this->json_input();
        $this->BusinessModel->update($id, $in);
        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function approve(int $id = 0): void
    {
        $this->require_any_permission(['app.services.requests.businesses.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $this->BusinessModel->update($id, [
            'status' => 'active',
            'verification_note' => null,
            'approved_by' => (int)$this->auth_user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        // Send WA Notification
        $person_id = (int)($row['owner_person_id'] ?? 0);
        if ($person_id) {
            $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
            if ($person && !empty($person['phone'])) {
                $nama = $person['full_name'] ?? 'Warga';
                $bizName = $row['name'] ?? 'Lapak/UMKM';
                $wa_msg = "Assalamu’alaikum, {$nama}\n\nAlhamdulillah, pengajuan lapak *{$bizName}* Anda telah disetujui.\nSilakan mulai menggunakan layanan pengelolaan lapak yang tersedia.\n\nSemoga usahanya senantiasa dilancarkan dan membawa kesuksesan.\n\n—\nPesan ini dikirim otomatis melalui layanan SIS Paguyuban";
                $this->whatsapp->send_message($person['phone'], $wa_msg);
            }
        }

        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function reject(int $id = 0): void
    {
        $this->require_any_permission(['app.services.requests.businesses.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $in = $this->json_input();
        $reason = trim((string)($in['reason'] ?? ''));
        if ($reason === '') {
            api_validation_error(['reason' => 'Wajib diisi']);
            return;
        }

        $this->BusinessModel->update($id, [
            'status' => 'rejected',
            'verification_note' => $reason,
            'approved_by' => (int)$this->auth_user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        // Send WA Notification
        $person_id = (int)($row['owner_person_id'] ?? 0);
        if ($person_id) {
            $person = $this->db->get_where('persons', ['id' => $person_id])->row_array();
            if ($person && !empty($person['phone'])) {
                $nama = $person['full_name'] ?? 'Warga';
                $bizName = $row['name'] ?? 'Lapak/UMKM';
                $wa_msg = "Assalamu’alaikum, {$nama}\n\nTerima kasih atas pengajuan lapak *{$bizName}* yang telah disampaikan.\nUntuk saat ini, pengajuan tersebut belum dapat diproses (Perlu Perbaikan) dengan alasan berikut:\n\n{$reason}\n\nSilakan diperbaiki datanya, atau dikomunikasikan dengan pengurus apabila diperlukan.\n\n—\nPesan ini dikirim otomatis melalui layanan SIS Paguyuban";
                $this->whatsapp->send_message($person['phone'], $wa_msg);
            }
        }

        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function resubmit(int $id = 0): void
    {
        $this->require_any_permission(['app.profile.family.umkm']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $row = $this->BusinessModel->find_by_id($id);
        if (!$row) {
            api_not_found();
            return;
        }

        $pid = (int)($this->auth_user['person_id'] ?? 0);
        if ((int)($row['owner_person_id'] ?? 0) !== $pid) {
            api_error('FORBIDDEN', 'Akses ditolak', 403);
            return;
        }

        if (($row['status'] ?? '') !== 'rejected') {
            api_error('INVALID_STATE', 'Hanya bisa ajukan ulang jika status Perlu perbaikan', 422);
            return;
        }

        $this->BusinessModel->update($id, [
            'status' => 'pending',
            'verification_note' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        api_ok($this->BusinessModel->find_by_id($id));
    }

    public function products(int $id = 0): void
    {
        $this->require_any_permission(['app.services.resident.umkm.view', 'app.profile.family.umkm', 'app.services.requests.businesses.review']);
        if ($id <= 0) {
            api_not_found();
            return;
        }
        $biz = $this->BusinessModel->find_by_id($id);
        if (!$biz) {
            api_not_found();
            return;
        }

        $can_review = $this->has_permission('app.services.requests.businesses.review');
        $pid = (int)($this->auth_user['person_id'] ?? 0);
        $is_owner = ((int)($biz['owner_person_id'] ?? 0) === $pid);

        if (!$can_review && !$is_owner && ($biz['status'] ?? '') !== 'active') {
            api_error('FORBIDDEN', 'Lapak belum aktif', 403);
            return;
        }

        $status = (!$can_review && !$is_owner) ? 'active' : null; // null = all
        $items = $this->ProductModel->list_by_business($id, $status);
        api_ok(['items' => $items]);
    }
}
