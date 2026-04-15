<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Qurban_model extends MY_Model
{
    protected string $table_name = 'qurban_periods';

    public function period(int $id): ?array
    {
        $row = $this->db
            ->select('p.*, la.name AS ledger_account_name, la.type AS ledger_account_type')
            ->from('qurban_periods p')
            ->join('ledger_accounts la', 'la.id = p.ledger_account_id', 'left')
            ->where('p.id', $id)
            ->get()->row_array();
        return $row ?: null;
    }

    public function active_period(): ?array
    {
        $row = $this->db
            ->select('p.*, la.name AS ledger_account_name, la.type AS ledger_account_type')
            ->from('qurban_periods p')
            ->join('ledger_accounts la', 'la.id = p.ledger_account_id', 'left')
            ->where('p.status', 'active')
            ->order_by('p.year', 'DESC')
            ->order_by('p.id', 'DESC')
            ->limit(1)
            ->get()->row_array();
        return $row ?: null;
    }

    public function periods(array $filters = []): array
    {
        $qb = $this->db
            ->select('p.*, la.name AS ledger_account_name, la.type AS ledger_account_type')
            ->from('qurban_periods p')
            ->join('ledger_accounts la', 'la.id = p.ledger_account_id', 'left');

        if (!empty($filters['status'])) {
            $qb->where('p.status', (string)$filters['status']);
        }

        return $qb->order_by('p.year', 'DESC')->order_by('p.id', 'DESC')->get()->result_array();
    }

    public function create_period(array $in): int
    {
        $this->db->insert('qurban_periods', [
            'year' => (int)$in['year'],
            'name' => trim((string)$in['name']),
            'description' => isset($in['description']) ? trim((string)$in['description']) : null,
            'starts_at' => $in['starts_at'] ?? null,
            'ends_at' => $in['ends_at'] ?? null,
            'status' => $in['status'] ?? 'draft',
            'ledger_account_id' => (int)$in['ledger_account_id'],
            'created_by' => $in['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_period(int $id, array $in): void
    {
        $allowed = ['year', 'name', 'description', 'starts_at', 'ends_at', 'status', 'ledger_account_id'];
        $upd = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $in)) {
                continue;
            }
            $upd[$key] = is_string($in[$key]) ? trim((string)$in[$key]) : $in[$key];
        }
        if (!$upd) {
            return;
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('qurban_periods', $upd);
    }

    public function set_period_status(int $id, string $status): void
    {
        $this->db->where('id', $id)->update('qurban_periods', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function animals(int $period_id, bool $resident_view = false): array
    {
        $qb = $this->db->from('qurban_animals')->where('period_id', $period_id);
        if ($resident_view) {
            $qb->where_in('status', ['available', 'full']);
            $qb->where('slot_no', 1);
        }
        $items = $qb->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get()->result_array();
        return $this->with_group_stats($items);
    }

    public function animal(int $id): ?array
    {
        $row = $this->db->from('qurban_animals')->where('id', $id)->get()->row_array();
        return $row ?: null;
    }

    public function create_animal(array $in): int
    {
        $package_code = $this->package_code($in);
        $this->db->insert('qurban_animals', [
            'period_id' => (int)$in['period_id'],
            'package_type' => $in['package_type'] ?? 'managed',
            'animal_type' => $in['animal_type'] ?? 'cow',
            'name' => trim((string)$in['name']),
            'package_code' => $package_code,
            'slot_no' => (int)($in['slot_no'] ?? 1),
            'price' => (float)$in['price'],
            'weight_kg' => isset($in['weight_kg']) && $in['weight_kg'] !== '' ? (float)$in['weight_kg'] : null,
            'quota' => (int)$in['quota'],
            'notes' => isset($in['notes']) ? trim((string)$in['notes']) : null,
            'sort_order' => (int)($in['sort_order'] ?? 0),
            'auto_rollover' => !empty($in['auto_rollover']) ? 1 : 0,
            'status' => $in['status'] ?? 'available',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_animal(int $id, array $in): void
    {
        $allowed = ['package_type', 'animal_type', 'name', 'price', 'weight_kg', 'quota', 'notes', 'sort_order', 'auto_rollover', 'status'];
        $upd = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $in)) {
                continue;
            }
            $upd[$key] = is_string($in[$key]) ? trim((string)$in[$key]) : $in[$key];
        }
        if (!$upd) {
            return;
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('qurban_animals', $upd);
    }

    public function resolve_animal_for_participation(array $animal): ?array
    {
        $status = (string)($animal['status'] ?? 'available');
        if ($status === 'closed') {
            return null;
        }
        if ($status === 'full' && (int)($animal['auto_rollover'] ?? 0) !== 1) {
            return null;
        }
        return $animal;
    }

    public function create_participation(array $in): int
    {
        $this->ensure_participation_manual_schema();

        $row = [
            'period_id' => (int)$in['period_id'],
            'animal_id' => (int)$in['animal_id'],
            'person_id' => !empty($in['person_id']) ? (int)$in['person_id'] : null,
            'participant_name' => trim((string)$in['participant_name']),
            'quantity' => 1,
            'amount' => (float)$in['amount'],
            'group_no' => (int)($in['group_no'] ?? 1),
            'status' => 'joined',
            'self_brought_animal_type' => $in['self_brought_animal_type'] ?? null,
            'self_brought_weight_kg' => isset($in['self_brought_weight_kg']) && $in['self_brought_weight_kg'] !== '' ? (float)$in['self_brought_weight_kg'] : null,
            'self_brought_notes' => $in['self_brought_notes'] ?? null,
            'note' => $in['note'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->field_exists('participant_source', 'qurban_participations')) {
            $row['participant_source'] = !empty($in['participant_source'])
                ? trim((string)$in['participant_source'])
                : (!empty($in['person_id']) ? 'resident' : 'manual');
        }
        if ($this->db->field_exists('manual_contact', 'qurban_participations')) {
            $row['manual_contact'] = isset($in['manual_contact']) ? trim((string)$in['manual_contact']) : null;
        }
        if ($this->db->field_exists('created_by', 'qurban_participations')) {
            $row['created_by'] = !empty($in['created_by']) ? (int)$in['created_by'] : null;
        }
        $this->db->insert('qurban_participations', $row);
        $id = (int)$this->db->insert_id();
        $this->refresh_animal_counts((int)$in['animal_id']);
        return $id;
    }

    private function ensure_participation_manual_schema(): void
    {
        if (!$this->db->table_exists('qurban_participations')) {
            return;
        }

        $personField = $this->db
            ->query("SHOW COLUMNS FROM `qurban_participations` LIKE 'person_id'")
            ->row_array();
        if ($personField && strtoupper((string)($personField['Null'] ?? '')) === 'NO') {
            $type = (string)($personField['Type'] ?? 'int');
            $this->db->query("ALTER TABLE `qurban_participations` MODIFY `person_id` {$type} NULL");
        }

        if (!$this->db->field_exists('participant_source', 'qurban_participations')) {
            $this->db->query("ALTER TABLE `qurban_participations` ADD COLUMN `participant_source` VARCHAR(24) NOT NULL DEFAULT 'resident' AFTER `participant_name`");
        }

        if (!$this->db->field_exists('manual_contact', 'qurban_participations')) {
            $this->db->query("ALTER TABLE `qurban_participations` ADD COLUMN `manual_contact` VARCHAR(191) NULL AFTER `participant_source`");
        }
    }

    public function participation(int $id): ?array
    {
        $row = $this->db
            ->select('q.*, a.name AS animal_name, a.package_type, a.package_code, a.slot_no, a.animal_type, p.year, p.name AS period_name, p.ledger_account_id, per.full_name')
            ->from('qurban_participations q')
            ->join('qurban_animals a', 'a.id = q.animal_id', 'left')
            ->join('qurban_periods p', 'p.id = q.period_id', 'left')
            ->join('persons per', 'per.id = q.person_id', 'left')
            ->where('q.id', $id)
            ->get()->row_array();
        return $row ?: null;
    }

    public function my_participations(int $person_id, ?int $period_id = null): array
    {
        $qb = $this->db
            ->select('q.*, a.name AS animal_name, a.package_type, a.package_code, a.slot_no, a.animal_type, p.year, p.name AS period_name')
            ->from('qurban_participations q')
            ->join('qurban_animals a', 'a.id = q.animal_id', 'left')
            ->join('qurban_periods p', 'p.id = q.period_id', 'left')
            ->where('q.person_id', $person_id);
        if ($period_id) {
            $qb->where('q.period_id', $period_id);
        }
        return $qb->order_by('q.id', 'DESC')->get()->result_array();
    }

    public function public_participations(int $period_id): array
    {
        $subHm = $this->db
            ->select('person_id, MAX(id) AS hm_id', false)
            ->from('household_members')
            ->group_by('person_id')
            ->get_compiled_select();
        $subHo = $this->db
            ->select('household_id, MAX(id) AS ho_id', false)
            ->from('house_occupancies')
            ->where('status', 'active')
            ->where('household_id IS NOT NULL', null, false)
            ->group_by('household_id')
            ->get_compiled_select();

        return $this->db
            ->select('q.id, q.period_id, q.animal_id, q.participant_name, q.manual_contact, q.group_no, q.status, q.created_at, a.name AS animal_name, a.package_type, a.package_code, a.slot_no, a.animal_type, h.code AS house_code')
            ->from('qurban_participations q')
            ->join('qurban_animals a', 'a.id = q.animal_id', 'left')
            ->join("($subHm) hmx", "hmx.person_id = q.person_id", "left")
            ->join("household_members hm", "hm.id = hmx.hm_id", "left")
            ->join("($subHo) hox", "hox.household_id = hm.household_id", "left")
            ->join("house_occupancies ho", "ho.id = hox.ho_id", "left")
            ->join("houses h", "h.id = ho.house_id", "left")
            ->where('q.period_id', $period_id)
            ->where_in('q.status', ['joined', 'pending_payment', 'paid'])
            ->order_by('a.sort_order', 'ASC')
            ->order_by('a.id', 'ASC')
            ->order_by('q.group_no', 'ASC')
            ->order_by('q.id', 'ASC')
            ->get()->result_array();
    }

    public function participations(array $filters = []): array
    {
        $subHm = $this->db
            ->select('person_id, MAX(id) AS hm_id', false)
            ->from('household_members')
            ->group_by('person_id')
            ->get_compiled_select();
        $subHo = $this->db
            ->select('household_id, MAX(id) AS ho_id', false)
            ->from('house_occupancies')
            ->where('status', 'active')
            ->where('household_id IS NOT NULL', null, false)
            ->group_by('household_id')
            ->get_compiled_select();

        $qb = $this->db
            ->select('q.*, a.name AS animal_name, a.package_type, a.package_code, a.slot_no, a.animal_type, p.year, p.name AS period_name, per.full_name, h.code AS house_code')
            ->from('qurban_participations q')
            ->join('qurban_animals a', 'a.id = q.animal_id', 'left')
            ->join('qurban_periods p', 'p.id = q.period_id', 'left')
            ->join('persons per', 'per.id = q.person_id', 'left')
            ->join("($subHm) hmx", "hmx.person_id = q.person_id", "left")
            ->join("household_members hm", "hm.id = hmx.hm_id", "left")
            ->join("($subHo) hox", "hox.household_id = hm.household_id", "left")
            ->join("house_occupancies ho", "ho.id = hox.ho_id", "left")
            ->join("houses h", "h.id = ho.house_id", "left");

        if (!empty($filters['period_id'])) {
            $qb->where('q.period_id', (int)$filters['period_id']);
        }
        if (!empty($filters['status'])) {
            $qb->where('q.status', (string)$filters['status']);
        }
        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);
            $qb->group_start()
                ->like('q.participant_name', $q)
                ->or_like('per.full_name', $q)
                ->or_like('a.name', $q)
                ->group_end();
        }

        return $qb->order_by('q.id', 'DESC')->get()->result_array();
    }

    public function cancel_participation(int $id): void
    {
        $row = $this->participation($id);
        $this->db->where('id', $id)->where('status !=', 'paid')->update('qurban_participations', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($row) {
            $this->refresh_animal_counts((int)$row['animal_id']);
        }
    }

    public function update_participation(int $id, array $in): void
    {
        $upd = [];
        if (array_key_exists('participant_name', $in)) {
            $upd['participant_name'] = trim((string)$in['participant_name']);
        }
        if (array_key_exists('manual_contact', $in) && $this->db->field_exists('manual_contact', 'qurban_participations')) {
            $upd['manual_contact'] = trim((string)$in['manual_contact']) ?: null;
        }
        if (array_key_exists('note', $in)) {
            $upd['note'] = trim((string)$in['note']) ?: null;
        }
        if (!$upd) {
            return;
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->where('status !=', 'cancelled')->update('qurban_participations', $upd);
    }

    public function mark_payment_submitted(int $id, array $in): void
    {
        $this->db->where('id', $id)->where('status !=', 'paid')->update('qurban_participations', [
            'status' => 'pending_payment',
            'paid_at' => $in['paid_at'],
            'proof_file_url' => $in['proof_file_url'],
            'note' => $in['note'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function approve_payment(int $id, int $verified_by, int $ledger_entry_id, ?string $paid_at = null): void
    {
        $payload = [
            'status' => 'paid',
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
            'ledger_entry_id' => $ledger_entry_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($paid_at) {
            $payload['paid_at'] = $paid_at;
        }
        $this->db->where('id', $id)->update('qurban_participations', $payload);
        $row = $this->participation($id);
        if ($row) {
            $this->refresh_animal_counts((int)$row['animal_id']);
        }
    }

    public function reject_payment(int $id, int $verified_by, ?string $note = null): void
    {
        $this->db->where('id', $id)->update('qurban_participations', [
            'status' => 'payment_rejected',
            'verified_by' => $verified_by,
            'verified_at' => date('Y-m-d H:i:s'),
            'note' => $note,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function refresh_animal_counts(int $animal_id): void
    {
        $row = $this->db->select(
            "SUM(CASE WHEN status IN ('joined','pending_payment','paid') THEN quantity ELSE 0 END) AS booked_count, " .
            "SUM(CASE WHEN status = 'paid' THEN quantity ELSE 0 END) AS paid_count",
            false
        )->from('qurban_participations')->where('animal_id', $animal_id)->get()->row_array();

        $animal = $this->animal($animal_id);
        $booked = (int)($row['booked_count'] ?? 0);
        $quota = (int)($animal['quota'] ?? 0);
        $status = (string)($animal['status'] ?? 'available');
        if ($status !== 'closed') {
            $status = ((int)($animal['auto_rollover'] ?? 0) !== 1 && $quota > 0 && $booked >= $quota) ? 'full' : 'available';
        }

        $this->db->where('id', $animal_id)->update('qurban_animals', [
            'booked_count' => $booked,
            'paid_count' => (int)($row['paid_count'] ?? 0),
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function package_code(array $in): string
    {
        $raw = strtolower(trim((string)($in['package_type'] ?? 'managed')) . '-' . trim((string)($in['animal_type'] ?? 'cow')) . '-' . trim((string)($in['name'] ?? '')) . '-' . (int)($in['price'] ?? 0) . '-' . (int)($in['quota'] ?? 1));
        $code = preg_replace('/[^a-z0-9]+/', '-', $raw);
        return trim((string)$code, '-') ?: 'qurban-package';
    }

    public function next_group_no(array $animal): int
    {
        $quota = max(1, (int)($animal['quota'] ?? 1));
        $row = $this->db
            ->select('COUNT(q.id) AS total', false)
            ->from('qurban_participations q')
            ->where('q.animal_id', (int)$animal['id'])
            ->where_in('q.status', ['joined', 'pending_payment', 'paid'])
            ->get()->row_array();
        $total = (int)($row['total'] ?? 0);
        return (int)floor($total / $quota) + 1;
    }

    private function with_group_stats(array $items): array
    {
        foreach ($items as &$item) {
            $quota = max(1, (int)($item['quota'] ?? 1));
            $booked = (int)($item['booked_count'] ?? 0);
            $item['completed_groups'] = (int)floor($booked / $quota);
            $item['current_group_no'] = (int)floor($booked / $quota) + 1;
            $item['current_group_count'] = $booked % $quota;
        }
        return $items;
    }
}
