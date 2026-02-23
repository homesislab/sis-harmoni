<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Onboarding extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();

        $this->load->model('House_model', 'HouseModel');
        $this->load->model('Household_model', 'HouseholdModel');
        $this->load->model('Person_model', 'PersonModel');
        $this->load->model('User_model', 'UserModel');
        $this->load->model('House_claim_model', 'HouseClaimModel');
        $this->load->model('Vehicle_model', 'VehicleModel');
    }

    public function units(): void
    {
        $p = $this->get_pagination_params();
        $page = $p['page'];
        $per  = $p['per_page'];

        $block = strtoupper(trim((string)($this->input->get('block') ?? '')));
        $q = trim((string)($this->input->get('q') ?? ''));

        $offset = ($page - 1) * $per;

        $qb = $this->db->from('houses h');

        $qb->where("NOT EXISTS (SELECT 1 FROM house_occupancies ho WHERE ho.house_id=h.id AND ho.status='active')", null, false);
        $qb->where("NOT EXISTS (SELECT 1 FROM house_claims hc WHERE hc.house_id=h.id AND hc.status='pending')", null, false);

        if ($block !== '') {
            $qb->where('h.block', $block);
        }
        if ($q !== '') {
            $qb->group_start()
                ->like('h.code', $q)
                ->or_like('h.number', $q)
                ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $rows = $qb->select('h.id,h.code,h.block,h.number,h.type,h.status')
            ->order_by('h.block', 'ASC')
            ->order_by('CAST(h.number AS UNSIGNED)', 'ASC', false)
            ->limit($per, $offset)
            ->get()->result_array();

        $items = array_map(function ($h) {
            return [
                'id' => $h['id'],
                'code' => $h['code'],
                'block' => $h['block'],
                'number' => $h['number'],
                'type' => $h['type'],
                'status' => $h['status'],
            ];
        }, $rows);

        api_ok(['items' => $items], [
            'page' => $page,
            'per_page' => $per,
            'total' => $total,
        ]);
    }

    public function blocks(): void
    {
        $rows = $this->db->select('DISTINCT h.block AS block', false)
            ->from('houses h')
            ->where("NOT EXISTS (SELECT 1 FROM house_occupancies ho WHERE ho.house_id=h.id AND ho.status='active')", null, false)
            ->where("NOT EXISTS (SELECT 1 FROM house_claims hc WHERE hc.house_id=h.id AND hc.status='pending')", null, false)
            ->order_by('h.block', 'ASC')
            ->get()->result_array();

        $blocks = [];
        foreach ($rows as $r) {
            $b = strtoupper(trim((string)($r['block'] ?? '')));
            if ($b !== '') {
                $blocks[] = $b;
            }
        }
        api_ok(['items' => $blocks]);
    }

    public function check_username(): void
    {
        $username = trim((string)($this->input->get('username') ?? ''));
        if ($username === '' || strlen($username) < 4) {
            api_validation_error(['username' => 'Minimal 4 karakter'], 'Validasi gagal');
            return;
        }

        $exists = $this->UserModel->find_by_username($username);
        api_ok(['available' => $exists ? false : true]);
    }

    public function register(): void
    {
        $in = $this->json_input();

        $household = $in['household'] ?? [];
        $head = $in['head'] ?? [];
        $members = $in['members'] ?? [];
        $vehicles = $in['vehicles'] ?? [];
        $units = $in['units'] ?? [];
        $account = $in['account'] ?? [];

        $kk = trim((string)($household['kk_number'] ?? ''));
        if ($kk === '') {
            api_validation_error(['kk_number' => 'Wajib diisi'], 'Validasi gagal');
            return;
        }

        $errHead = $this->PersonModel->validate_payload($head, true);
        if ($errHead) {
            api_validation_error($errHead, 'Data kepala keluarga belum lengkap');
            return;
        }

        $username = trim((string)($account['username'] ?? ''));
        $password = (string)($account['password'] ?? '');
        if ($username === '' || strlen($username) < 4 || strlen($password) < 6) {
            api_validation_error([
                'username' => 'Minimal 4 karakter',
                'password' => 'Minimal 6 karakter'
            ], 'Akun belum lengkap');
            return;
        }

        if (!is_array($units) || count($units) === 0) {
            api_validation_error(['units' => 'Pilih minimal 1 unit'], 'Unit belum dipilih');
            return;
        }

        $allowed_claim_types = ['owner','tenant'];
        $allowed_unit_types = ['house','kavling'];

        $primary_count = 0;
        $has_tenant = false;

        foreach ($units as $u) {
            if (!is_array($u)) {
                continue;
            }

            $houseId = (int)($u['house_id'] ?? 0);
            if ($houseId <= 0) {
                api_validation_error(['units' => 'house_id tidak valid'], 'Unit belum valid');
                return;
            }

            $ct = strtolower((string)($u['claim_type'] ?? 'owner'));
            if (!in_array($ct, $allowed_claim_types, true)) {
                api_validation_error(['units' => 'claim_type tidak valid'], 'Unit belum valid');
                return;
            }

            $ut = $u['unit_type'] ?? null;
            $ut = ($ut !== null && $ut !== '') ? strtolower((string)$ut) : null;
            if ($ut !== null && !in_array($ut, $allowed_unit_types, true)) {
                api_validation_error(['units' => 'unit_type tidak valid'], 'Unit belum valid');
                return;
            }

            $isPrimary = (int)($u['is_primary'] ?? 0) === 1 ? 1 : 0;

            if ($ct === 'tenant') {
                $has_tenant = true;
            }

            if ($isPrimary === 1) {
                $primary_count++;

                if ($ut !== null && $ut !== 'house') {
                    api_validation_error(['units' => 'Alamat utama hanya bisa untuk unit rumah'], 'Unit belum valid');
                    return;
                }
            }

            if ($ct === 'tenant' && $isPrimary !== 1) {
                api_validation_error(
                    ['units' => 'Jika statusnya penghuni kontrak, pilih unit tersebut sebagai alamat utama'],
                    'Unit belum valid'
                );
                return;
            }

            $hasOcc = (int)$this->db
                ->from('house_occupancies')
                ->where('house_id', $houseId)
                ->where('status', 'active')
                ->count_all_results();
            if ($hasOcc > 0) {
                api_conflict('Unit sudah ditempati / tidak tersedia untuk pendaftaran.');
                return;
            }

            $hasPending = (int)$this->db
                ->from('house_claims')
                ->where('house_id', $houseId)
                ->where('status', 'pending')
                ->count_all_results();
            if ($hasPending > 0) {
                api_conflict('Unit sedang dalam proses klaim (menunggu persetujuan).');
                return;
            }
        }

        if ($primary_count > 1) {
            api_validation_error(['units' => 'Pilih maksimal 1 alamat utama'], 'Terlalu banyak alamat utama');
            return;
        }

        if ($has_tenant && $primary_count !== 1) {
            api_validation_error(['units' => 'Pilih 1 alamat utama untuk penghuni kontrak'], 'Alamat utama belum dipilih');
            return;
        }

        $this->db->trans_start();
        try {
            $headPersonId = $this->PersonModel->create($head);

            $householdId = $this->HouseholdModel->create([
                'kk_number' => $kk,
                'head_person_id' => $headPersonId,
            ]);

            $this->HouseholdModel->add_member($householdId, $headPersonId, 'kepala_keluarga');

            if (is_array($members)) {
                foreach ($members as $m) {
                    if (!is_array($m)) {
                        continue;
                    }
                    $rel = trim((string)($m['relationship'] ?? 'anggota'));
                    $pdata = $m;
                    unset($pdata['relationship']);

                    if (trim((string)($pdata['full_name'] ?? '')) === '' && trim((string)($pdata['nik'] ?? '')) === '') {
                        continue;
                    }

                    $errM = $this->PersonModel->validate_payload($pdata, true);
                    if ($errM) {
                        throw new Exception('Data anggota keluarga belum lengkap');
                    }
                    $mid = $this->PersonModel->create($pdata);
                    $this->HouseholdModel->add_member($householdId, $mid, $rel);
                }
            }

            if ($this->UserModel->find_by_username($username)) {
                throw new Exception('Username sudah digunakan');
            }
            $userId = $this->UserModel->create([
                'person_id' => $headPersonId,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'status' => 'inactive',
            ]);
            $this->UserModel->assign_role_code($userId, 'resident');

            if (is_array($vehicles)) {
                foreach ($vehicles as $v) {
                    if (!is_array($v)) {
                        continue;
                    }
                    if (trim((string)($v['plate_number'] ?? '')) === '') {
                        continue;
                    }
                    $v['person_id'] = $headPersonId;
                    $errV = $this->VehicleModel->validate_payload($v, true);
                    if ($errV) {
                        throw new Exception('Data kendaraan belum lengkap');
                    }
                    $this->VehicleModel->create($v);
                }
            }

            foreach ($units as $u) {
                if (!is_array($u)) {
                    continue;
                }
                $houseId = (int)($u['house_id'] ?? 0);
                if ($houseId <= 0) {
                    continue;
                }

                $claimType = strtolower((string)($u['claim_type'] ?? 'owner'));
                $unitType = $u['unit_type'] ?? null;
                $unitType = ($unitType !== null && $unitType !== '') ? strtolower((string)$unitType) : null;
                $isPrimary = (int)($u['is_primary'] ?? 0) === 1 ? 1 : 0;
                $note = $u['note'] ?? 'Pendaftaran warga (onboarding)';

                $this->HouseClaimModel->create([
                    'house_id' => $houseId,
                    'person_id' => $headPersonId,
                    'claim_type' => $claimType,
                    'unit_type' => $unitType,
                    'is_primary' => $isPrimary,
                    'note' => $note,
                ]);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                api_error('INTERNAL', 'Belum bisa diproses. Coba lagi.', 500);
                return;
            }

            api_ok([
                'user_id' => $userId,
                'person_id' => $headPersonId,
                'household_id' => $householdId,
                'status' => 'pending_review',
            ], ['message' => 'Pendaftaran diterima, menunggu review pengurus'], 201);

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            api_error('VALIDATION', $e->getMessage(), 422);
        }
    }
}
