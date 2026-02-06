<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emergency_report_model extends CI_Model
{
    private string $table = 'emergency_reports';

    private function qb_with_joins()
    {

        $qb = $this->db->from($this->table . ' er');

        $qb->join('persons p_reporter', 'p_reporter.id = er.reporter_person_id', 'left');

        $qb->join('houses h', 'h.id = er.house_id', 'left');

        $qb->join('users u_ack', 'u_ack.id = er.acknowledged_by', 'left');
        $qb->join('persons p_ack', 'p_ack.id = u_ack.person_id', 'left');

        $qb->join('users u_res', 'u_res.id = er.resolved_by', 'left');
        $qb->join('persons p_res', 'p_res.id = u_res.person_id', 'left');

        $qb->join('users u_created', 'u_created.id = er.created_by', 'left');
        $qb->join('persons p_created', 'p_created.id = u_created.person_id', 'left');

        $qb->select([
            'er.*',

            'p_reporter.full_name AS reporter_name',
            'p_reporter.phone AS reporter_phone',

            "COALESCE(h.code, CONCAT(h.block, '-', h.number)) AS house_code",
            'h.block AS house_block',
            'h.number AS house_number',

            'p_ack.full_name AS acknowledged_by_name',
            'p_res.full_name AS resolved_by_name',
            'p_created.full_name AS created_by_name',
        ]);

        return $qb;
    }

    private function apply_search(?string $q): void
    {
        $q = is_string($q) ? trim($q) : '';
        if ($q === '') return;

        $this->db->group_start()
            ->like('er.description', $q)
            ->or_like('er.location_text', $q)
            ->or_like('p_reporter.full_name', $q)
            ->or_like('p_reporter.phone', $q)
            ->or_like('h.code', $q)
            ->or_like('h.block', $q)
            ->or_like('h.number', $q)
            ->or_like('er.id', $q)
        ->group_end();
    }

    public function find_by_id(int $id): ?array
    {
        $qb = $this->qb_with_joins();
        $row = $qb->where('er.id', $id)->get()->row_array();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = [
            'reporter_person_id' => isset($data['reporter_person_id']) ? (int)$data['reporter_person_id'] : null,
            'house_id' => isset($data['house_id']) ? (int)$data['house_id'] : null,
            'type' => $data['type'] ?? 'other',
            'description' => $data['description'] ?? null,
            'location_text' => $data['location_text'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => $data['status'] ?? 'open',
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = [
            'type','description','location_text','contact_phone','status',
            'acknowledged_by','acknowledged_at',
            'resolved_by','resolved_at','resolution_note'
        ];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) $upd[$k] = $data[$k];
        }
        if ($upd) {
            $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->update($this->table, $upd);
        }
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->qb_with_joins();

        if (!empty($filters['status'])) $qb->where('er.status', (string)$filters['status']);
        if (!empty($filters['type'])) $qb->where('er.type', (string)$filters['type']);
        if (!empty($filters['reporter_person_id'])) $qb->where('er.reporter_person_id', (int)$filters['reporter_person_id']);

        if (!empty($filters['q'])) $this->apply_search((string)$filters['q']);

        $count_qb = clone $qb;
        $count_qb->select('COUNT(1) AS cnt', false);
        $total = (int)($count_qb->get()->row_array()['cnt'] ?? 0);

        $items = $qb->order_by('er.id', 'DESC')->limit($per, $offset)->get()->result_array();
        $total_pages = ($per > 0 ? (int)ceil($total / $per) : 0);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_prev' => $page > 1,
                'has_next' => $page < $total_pages,
            ],
        ];
    }

}
