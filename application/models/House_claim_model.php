<?php

defined('BASEPATH') or exit('No direct script access allowed');

class House_claim_model extends MY_Model
{
    protected string $table_name = 'house_claims';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('hc.*, h.code as house_code, p.full_name as person_name')
            ->from('house_claims hc')
            ->join('houses h', 'h.id=hc.house_id', 'left')
            ->join('persons p', 'p.id=hc.person_id', 'left')
            ->where('hc.id', $id)
            ->get()->row_array();
        return $row ?: null;
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $q = $this->db->select('hc.*, h.code as house_code, p.full_name as person_name')
            ->from('house_claims hc')
            ->join('houses h', 'h.id=hc.house_id', 'left')
            ->join('persons p', 'p.id=hc.person_id', 'left');

        if (!empty($filters['source'])) {
            $q->where('hc.source', $filters['source']);
        }
        if (!empty($filters['status'])) {
            $q->where('hc.status', $filters['status']);
        }
        if (!empty($filters['claim_type'])) {
            $q->where('hc.claim_type', $filters['claim_type']);
        }
        if (!empty($filters['house_id'])) {
            $q->where('hc.house_id', (int)$filters['house_id']);
        }
        if (!empty($filters['person_id'])) {
            $q->where('hc.person_id', (int)$filters['person_id']);
        }

        if (!empty($filters['q'])) {
            $kw = trim((string)$filters['q']);
            if ($kw !== '') {
                $q->group_start()
                    ->like('h.code', $kw)
                    ->or_like('p.full_name', $kw)
                    ->or_like('hc.claim_type', $kw)
                    ->or_like('hc.unit_type', $kw)
                    ->or_like('hc.status', $kw)
                ->group_end();
            }
        }

        $totalQ = clone $q;
        $total = (int)$totalQ->count_all_results('', false);

        $items = $q->order_by('hc.id', 'desc')->limit($perPage, $offset)->get()->result_array();

        return [
            'items' => $items,
            'meta' => api_pagination_meta($page, $perPage, $total),
        ];
    }

    public function create(array $data): int
    {
        $unit_type = $data['unit_type'] ?? null;
        $unit_type = ($unit_type !== null && $unit_type !== '') ? (string)$unit_type : null;
        $is_primary = (int)($data['is_primary'] ?? 0) === 1 ? 1 : 0;

        $source = isset($data['source']) ? (string)$data['source'] : 'additional';
        if (!in_array($source, ['registration','additional'], true)) {
            $source = 'additional';
        }

        $this->db->insert('house_claims', [
            'house_id' => (int)$data['house_id'],
            'person_id' => (int)$data['person_id'],
            'claim_type' => $data['claim_type'],
            'unit_type' => $unit_type,
            'is_primary' => $is_primary,
            'status' => $data['status'] ?? 'pending',
            'source' => $source, // ✅
            'requested_at' => date('Y-m-d H:i:s'),
            'note' => $data['note'] ?? null,
        ]);
        return (int)$this->db->insert_id();
    }

    public function set_status(int $id, string $status, ?int $reviewed_by = null, ?string $note = null, ?string $reject_note = null): void
    {
        $payload = [
            'status' => $status,
            'reviewed_by' => $reviewed_by,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];
        if ($note !== null) {
            $payload['note'] = $note;
        }
        if ($reject_note !== null) {
            $payload['reject_note'] = $reject_note;
        }
        $this->db->where('id', $id)->update('house_claims', $payload);
    }

    public function has_open_claim(int $house_id, int $person_id): bool
    {
        $q = $this->db->select('id')
            ->from('house_claims')
            ->where('house_id', $house_id)
            ->where('person_id', $person_id)
            ->where_in('status', ['pending'])
            ->limit(1)
            ->get();

        return $q->num_rows() > 0;
    }

    public function review(int $id, string $status, int $reviewed_by, ?string $note = null, ?string $reject_note = null): void
    {
        $payload = [
            'status' => $status,
            'reviewed_by' => $reviewed_by,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        if ($note !== null) {
            $payload['note'] = $note;
        }
        if ($reject_note !== null) {
            $payload['reject_note'] = $reject_note;
        }

        $this->db->where('id', $id)->update('house_claims', $payload);
    }
}
