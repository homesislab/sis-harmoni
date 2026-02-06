<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Registrations extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->require_role(['admin']);
    }

    public function index(): void
    {
        $page = max(1, (int)$this->input->get('page'));
        $per  = min(100, max(1, (int)($this->input->get('per_page') ?: 20)));
        $offset = ($page - 1) * $per;

        $status = $this->input->get('status') ? strtolower((string)$this->input->get('status')) : null;
        $q = $this->input->get('q') ? trim((string)$this->input->get('q')) : '';

        $qb = $this->db
            ->select('hh.id, hh.kk_number, hh.created_at, p.full_name AS head_name, p.nik AS head_nik')
            ->from('households hh')
            ->join('persons p', 'p.id = hh.head_person_id', 'inner');

        if ($q !== '') {
            $qb->group_start()
                ->like('hh.kk_number', $q)
                ->or_like('p.full_name', $q)
                ->or_like('p.nik', $q)
            ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);
        $rows = $qb->order_by('hh.id', 'DESC')->limit($per, $offset)->get()->result_array();

        $items = [];
        foreach ($rows as $r) {
            $hid = (int)$r['id'];

            $ids = $this->get_household_person_ids($hid);

            $claim_rows = [];
            if ($ids) {
                $claim_rows = $this->db
                    ->select('hc.status, h.code AS house_code')
                    ->from('house_claims hc')
                    ->join('houses h', 'h.id = hc.house_id', 'inner')
                    ->where_in('hc.person_id', $ids)
                    ->order_by('hc.id', 'DESC')
                    ->get()->result_array();
            }

            $claim_statuses = array_map(fn($x) => $x['status'], $claim_rows);
            $derived = $this->compute_status($claim_statuses);

            if ($status && $derived !== $status) continue;

            $unit_codes = [];
            foreach ($claim_rows as $c) {
                if (!empty($c['house_code'])) $unit_codes[] = $c['house_code'];
            }
            $unit_codes = array_values(array_unique($unit_codes));

            $items[] = [
                'id' => $hid,
                'kk_number' => $r['kk_number'],
                'head_name' => $r['head_name'],
                'head_nik' => $r['head_nik'],
                'status' => $derived,
                'unit_codes' => $unit_codes,
                'created_at' => $r['created_at'],
            ];
        }

        $total2 = count($items);
        $paged = array_slice($items, 0, $per);

        api_ok(['items' => $paged], [
            'page' => $page,
            'per_page' => $per,
            'total' => $total2,
            'total_pages' => ($per > 0 ? (int)ceil($total2 / $per) : 0),
            'has_prev' => $page > 1,
            'has_next' => false, // because we filtered after paginate
        ]);
    }

    public function show(int $household_id = 0): void
    {
        if ($household_id <= 0) { api_not_found(); return; }

        $hh = $this->db
            ->select('hh.*, p.full_name AS head_name, p.nik AS head_nik, p.phone AS head_phone, p.email AS head_email')
            ->from('households hh')
            ->join('persons p', 'p.id = hh.head_person_id', 'inner')
            ->where('hh.id', $household_id)
            ->get()->row_array();
        if (!$hh) { api_not_found(); return; }

        $members = $this->db
            ->select('hm.relationship, p.*')
            ->from('household_members hm')
            ->join('persons p', 'p.id = hm.person_id', 'inner')
            ->where('hm.household_id', $household_id)
            ->order_by('hm.id', 'ASC')
            ->get()->result_array();

        $person_ids = array_map(fn($x) => (int)$x['id'], $members);
        $person_ids[] = (int)$hh['head_person_id'];
        $person_ids = array_values(array_unique(array_filter($person_ids)));

        $vehicles = [];
        $claims = [];
        $ownerships = [];
        $occupancies = [];
        $users = [];

        if ($person_ids) {
            $vehicles = $this->db
                ->from('vehicles')
                ->where_in('person_id', $person_ids)
                ->order_by('id', 'DESC')
                ->get()->result_array();

            $claims = $this->db
                ->select('hc.*, h.code AS house_code, h.block, h.number, h.type AS house_type')
                ->from('house_claims hc')
                ->join('houses h', 'h.id = hc.house_id', 'inner')
                ->where_in('hc.person_id', $person_ids)
                ->order_by('hc.id', 'DESC')
                ->get()->result_array();

            $ownerships = $this->db
                ->select('ho.*, h.code AS house_code, p.full_name AS person_name')
                ->from('house_ownerships ho')
                ->join('houses h', 'h.id = ho.house_id', 'inner')
                ->join('persons p', 'p.id = ho.person_id', 'inner')
                ->where_in('ho.person_id', $person_ids)
                ->order_by('ho.id', 'DESC')
                ->get()->result_array();

            $users = $this->db
                ->select('id,person_id,username,email,status,created_at')
                ->from('users')
                ->where_in('person_id', $person_ids)
                ->order_by('id', 'DESC')
                ->get()->result_array();
        }

        $occupancies = $this->db
            ->select('oc.*, h.code AS house_code, h.block, h.number, h.type AS house_type')
            ->from('house_occupancies oc')
            ->join('houses h', 'h.id = oc.house_id', 'inner')
            ->where('oc.household_id', $household_id)
            ->order_by('oc.id', 'DESC')
            ->get()->result_array();

        $claim_statuses = array_map(fn($x) => $x['status'], $claims);
        $derived = $this->compute_status($claim_statuses);

        api_ok([
            'household' => $hh,
            'status' => $derived,
            'members' => $members,
            'vehicles' => $vehicles,
            'claims' => $claims,
            'ownerships' => $ownerships,
            'occupancies' => $occupancies,
            'users' => $users,
        ]);
    }

    public function approve(int $household_id = 0): void
    {
        if ($household_id <= 0) { api_not_found(); return; }

        $detail = $this->db->get_where('households', ['id' => $household_id])->row_array();
        if (!$detail) { api_not_found(); return; }

        $ids = $this->get_household_person_ids($household_id);

        $in = $this->json_input();
        $note = isset($in['note']) ? (string)$in['note'] : null;

        $pendingClaims = [];
        if ($ids) {
            $pendingClaims = $this->db
                ->from('house_claims')
                ->where_in('person_id', $ids)
                ->where('status', 'pending')
                ->order_by('id', 'ASC')
                ->get()->result_array();
        }

        if (!$pendingClaims) {
            api_conflict('Tidak ada klaim unit yang pending pada pendaftaran ini.');
            return;
        }

        $primaryClaims = array_values(array_filter(
            $pendingClaims,
            fn($c) => (int)($c['is_primary'] ?? 0) === 1
        ));
        $primary_count = count($primaryClaims);

        $has_tenant = false;
        foreach ($pendingClaims as $c) {
            if (strtolower((string)($c['claim_type'] ?? '')) === 'tenant') {
                $has_tenant = true;
                break;
            }
        }

        if ($primary_count > 1) {
            api_error('VALIDATION', 'Pilih maksimal 1 unit sebagai alamat utama.', 422);
            return;
        }

        if ($has_tenant && $primary_count !== 1) {
            api_error('VALIDATION', 'Penghuni kontrak wajib memilih 1 unit sebagai alamat utama.', 422);
            return;
        }

        if ($primary_count === 1) {
            $primary = $primaryClaims[0];
            $primaryUnitType = isset($primary['unit_type']) ? strtolower((string)$primary['unit_type']) : null;
            if ($primaryUnitType === 'kavling') {
                api_error('VALIDATION', 'Alamat utama tidak boleh kavling.', 422);
                return;
            }
        }

        $this->db->trans_start();

        try {
            $claimIds = array_map(fn($x) => (int)$x['id'], $pendingClaims);
            $this->db
                ->where_in('id', $claimIds)
                ->update('house_claims', [
                    'status' => 'approved',
                    'reviewed_by' => (int)$this->auth_user['id'],
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'note' => $note,
                ]);

            if ($ids) {
                $this->db
                    ->where_in('person_id', $ids)
                    ->where('status', 'inactive')
                    ->update('users', [
                        'status' => 'active',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            foreach ($pendingClaims as $c) {
                $houseId = (int)$c['house_id'];
                $personId = (int)$c['person_id'];
                $claimType = strtolower((string)$c['claim_type']); // owner|tenant
                $unitType = isset($c['unit_type']) ? strtolower((string)$c['unit_type']) : null; // house|kavling|null
                $isPrimary = (int)($c['is_primary'] ?? 0) === 1 ? 1 : 0;

                if ($unitType && in_array($unitType, ['house', 'kavling'], true)) {
                    $this->db->where('id', $houseId)->update('houses', ['type' => $unitType]);
                }

                if ($claimType === 'owner') {
                    $existing = $this->db
                        ->from('house_ownerships')
                        ->where('house_id', $houseId)
                        ->where('end_date IS NULL', null, false)
                        ->get()->row_array();

                    if ($existing && (int)$existing['person_id'] !== $personId) {
                        throw new Exception('Konflik kepemilikan: unit sudah punya pemilik aktif.');
                    }

                    if (!$existing) {
                        $this->db->insert('house_ownerships', [
                            'house_id' => $houseId,
                            'person_id' => $personId,
                            'start_date' => date('Y-m-d'),
                            'end_date' => null,
                            'note' => 'Approved from registration',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if ($isPrimary === 1) {
                        $this->end_active_household_occupancies((int)$household_id);

                        $conflict = (int)$this->db
                            ->from('house_occupancies')
                            ->where('house_id', $houseId)
                            ->where('status', 'active')
                            ->count_all_results();
                        if ($conflict > 0) {
                            throw new Exception('Konflik hunian: unit tempat tinggal sudah ditempati.');
                        }

                        $this->db->insert('house_occupancies', [
                            'house_id' => $houseId,
                            'household_id' => $household_id,
                            'occupancy_type' => 'owner_live',
                            'start_date' => date('Y-m-d'),
                            'end_date' => null,
                            'status' => 'active',
                            'note' => 'Approved from registration',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    $this->recompute_house_status($houseId);
                }

                if ($claimType === 'tenant') {
                    if ($isPrimary !== 1) {
                        throw new Exception('Penghuni kontrak harus memilih tempat tinggal utama.');
                    }

                    $this->end_active_household_occupancies((int)$household_id);

                    $conflict = (int)$this->db
                        ->from('house_occupancies')
                        ->where('house_id', $houseId)
                        ->where('status', 'active')
                        ->count_all_results();
                    if ($conflict > 0) {
                        throw new Exception('Konflik hunian: unit sudah ditempati household lain.');
                    }

                    if (!$unitType) {
                        $this->db->where('id', $houseId)->update('houses', ['type' => 'house']);
                    }

                    $this->db->insert('house_occupancies', [
                        'house_id' => $houseId,
                        'household_id' => $household_id,
                        'occupancy_type' => 'tenant',
                        'start_date' => date('Y-m-d'),
                        'end_date' => null,
                        'status' => 'active',
                        'note' => 'Approved from registration',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    $this->recompute_house_status($houseId);
                }
            }

            $this->db->trans_complete();
            if ($this->db->trans_status() === false) throw new Exception('Transaksi gagal');

            api_ok(['ok' => true]);

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            api_error('VALIDATION', $e->getMessage(), 422);
        }
    }

    public function reject(int $household_id = 0): void
    {
        if ($household_id <= 0) { api_not_found(); return; }

        $detail = $this->db->get_where('households', ['id' => $household_id])->row_array();
        if (!$detail) { api_not_found(); return; }

        $in = $this->json_input();
        $reason = trim((string)($in['reason'] ?? ''));
        if ($reason === '') {
            api_validation_error(['reason' => 'Wajib diisi']);
            return;
        }

        $ids = $this->get_household_person_ids($household_id);

        $this->db->trans_start();

        try {
            if ($ids) {
                $this->db
                    ->where_in('person_id', $ids)
                    ->where('status', 'pending')
                    ->update('house_claims', [
                        'status' => 'rejected',
                        'reviewed_by' => (int)$this->auth_user['id'],
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'reject_note' => $reason,
                    ]);

            }

            $this->db->trans_complete();
            if ($this->db->trans_status() === false) throw new Exception('Transaksi gagal');

            api_ok(['ok' => true]);

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            api_error('VALIDATION', $e->getMessage(), 422);
        }
    }

    private function compute_status(array $claim_statuses): string
    {
        $set = array_unique(array_map(fn($x) => strtolower((string)$x), $claim_statuses));
        if (in_array('pending', $set, true)) return 'pending';
        if (in_array('rejected', $set, true)) return 'rejected';
        if (in_array('approved', $set, true)) return 'approved';
        return 'pending';
    }

    private function get_household_person_ids(int $household_id): array
    {
        $detail = $this->db->get_where('households', ['id' => $household_id])->row_array();
        if (!$detail) return [];

        $person_ids = $this->db
            ->select('person_id')
            ->from('household_members')
            ->where('household_id', $household_id)
            ->get()->result_array();

        $ids = array_map(fn($x) => (int)$x['person_id'], $person_ids);
        $ids[] = (int)$detail['head_person_id'];
        $ids = array_values(array_unique(array_filter($ids)));

        return $ids;
    }

    private function end_active_household_occupancies(int $household_id): void
    {
        if ($household_id <= 0) return;

        $this->db
            ->where('household_id', $household_id)
            ->where('status', 'active')
            ->update('house_occupancies', [
                'status' => 'ended',
                'end_date' => date('Y-m-d'),
            ]);
    }

    private function recompute_house_status(int $house_id): void
    {
        if ($house_id <= 0) return;

        $occ = $this->db
            ->select('occupancy_type')
            ->from('house_occupancies')
            ->where('house_id', $house_id)
            ->where('status', 'active')
            ->order_by('id', 'DESC')
            ->get()->row_array();

        if ($occ) {
            $t = strtolower((string)$occ['occupancy_type']);
            if ($t === 'tenant') {
                $this->db->where('id', $house_id)->update('houses', ['status' => 'rented']);
                return;
            }
            $this->db->where('id', $house_id)->update('houses', ['status' => 'occupied']);
            return;
        }

        $own = $this->db
            ->from('house_ownerships')
            ->where('house_id', $house_id)
            ->where('end_date IS NULL', null, false)
            ->order_by('id', 'DESC')
            ->get()->row_array();

        if ($own) {
            $this->db->where('id', $house_id)->update('houses', ['status' => 'owned']);
            return;
        }

        $this->db->where('id', $house_id)->update('houses', ['status' => 'vacant']);
    }
}
