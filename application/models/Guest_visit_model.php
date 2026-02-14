<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Guest_visit_model extends CI_Model
{
    private string $table = 'guest_visits';

    public function create(array $data): int
    {
        $payload = [
            'house_id' => array_key_exists('house_id', $data) ? $data['house_id'] : null,
            'destination_type' => $data['destination_type'] ?? 'unit',
            'destination_label' => $data['destination_label'] ?? null,

            'host_person_id' => isset($data['host_person_id']) ? (int)$data['host_person_id'] : null,
            'visitor_name' => trim((string)$data['visitor_name']),
            'visitor_phone' => $data['visitor_phone'] ?? null,
            'purpose' => trim((string)$data['purpose']),
            'visitor_count' => isset($data['visitor_count']) ? (int)$data['visitor_count'] : 1,
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'visit_at' => (string)$data['visit_at'],
            'note' => $data['note'] ?? null,

            'status' => $data['status'] ?? 'checked_in',
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'checked_in_at' => $data['checked_in_at'] ?? null,
            'checked_out_at' => $data['checked_out_at'] ?? null,

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = [
            'house_id','destination_type','destination_label',
            'host_person_id','visitor_name','visitor_phone','purpose','visitor_count',
            'vehicle_plate','visit_at','note','status',
            'approved_by','approved_at','checked_in_at','checked_out_at'
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

    public function find_by_id(int $id): ?array
    {
        $row = $this->db
            ->select('gv.*, h.code as house_code, p.full_name as host_name')
            ->from('guest_visits gv')
            ->join('houses h','h.id=gv.house_id','left')
            ->join('persons p','p.id=gv.host_person_id','left')
            ->where('gv.id', $id)
            ->get()->row_array();

        return $row ?: null;
    }

    public function paginate(int $page, int $per, array $filters = []): array
    {
        $page = max(1, $page);
        $per = max(1, min(100, $per));
        $offset = ($page - 1) * $per;

        $qb = $this->db
            ->select('gv.*, h.code as house_code, p.full_name as host_name')
            ->from('guest_visits gv')
            ->join('houses h','h.id=gv.house_id','left')
            ->join('persons p','p.id=gv.host_person_id','left');

        if (!empty($filters['house_ids']) && is_array($filters['house_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['house_ids'])));
            if ($ids) $qb->where_in('gv.house_id', $ids);
        }

        if (!empty($filters['created_by'])) $qb->where('gv.created_by', (int)$filters['created_by']);
        if (!empty($filters['status'])) $qb->where('gv.status', (string)$filters['status']);
        if (!empty($filters['destination_type'])) $qb->where('gv.destination_type', (string)$filters['destination_type']);

        if (!empty($filters['q'])) {
            $kw = trim((string)$filters['q']);
            if ($kw !== '') {
                $qb->group_start()
                    ->like('gv.visitor_name', $kw)
                    ->or_like('gv.purpose', $kw)
                    ->or_like('gv.vehicle_plate', $kw)
                    ->or_like('gv.visitor_phone', $kw)
                    ->or_like('gv.note', $kw)
                    ->or_like('gv.destination_label', $kw)
                    ->or_like('h.code', $kw)
                    ->or_like('p.full_name', $kw)
                ->group_end();
            }
        }

        $total = (int)$qb->count_all_results('', false);
        $items = $qb->order_by('gv.id','DESC')->limit($per, $offset)->get()->result_array();
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
