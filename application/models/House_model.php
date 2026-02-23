<?php

defined('BASEPATH') or exit('No direct script access allowed');

class House_model extends MY_Model
{
    protected string $table_name = 'houses';

    public function update_status_type(int $house_id, ?string $type = null, ?string $status = null): void
    {
        $upd = [];
        if ($type !== null) {
            $upd['type'] = $type;
        }
        if ($status !== null) {
            $upd['status'] = $status;
        }
        if (!$upd) {
            return;
        }
        $this->db->where('id', $house_id)->update('houses', $upd);
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('houses', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function exists_code(string $code, int $exclude_id = 0): bool
    {
        $this->db->from('houses')->where('code', $code);
        if ($exclude_id > 0) {
            $this->db->where('id !=', $exclude_id);
        }
        return (int)$this->db->count_all_results() > 0;
    }

    public function exists_block_number(string $block, string $number, int $exclude_id = 0): bool
    {
        $this->db->from('houses')->where('block', $block)->where('number', $number);
        if ($exclude_id > 0) {
            $this->db->where('id !=', $exclude_id);
        }
        return (int)$this->db->count_all_results() > 0;
    }

    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];

        $block  = isset($in['block']) ? trim((string)$in['block']) : null;
        $number = isset($in['number']) ? trim((string)$in['number']) : null;
        $code   = isset($in['code']) ? trim((string)$in['code']) : null;
        $type   = isset($in['type']) ? (string)$in['type'] : null;
        $status = isset($in['status']) ? (string)$in['status'] : null;

        if ($is_create) {
            if ($block === null || $block === '') {
                $err['block'] = 'Wajib diisi';
            }
            if ($number === null || $number === '') {
                $err['number'] = 'Wajib diisi';
            }
            if ($code === null || $code === '') {
                $err['code'] = 'Wajib diisi';
            }
            if ($type === null || $type === '') {
                $err['type'] = 'Wajib diisi';
            }
            if ($status === null || $status === '') {
                $err['status'] = 'Wajib diisi';
            }
        }

        if ($block !== null && $block !== '' && strlen($block) > 10) {
            $err['block'] = 'Maks 10 karakter';
        }
        if ($number !== null && $number !== '' && strlen($number) > 10) {
            $err['number'] = 'Maks 10 karakter';
        }
        if ($code !== null && $code !== '' && strlen($code) > 30) {
            $err['code'] = 'Maks 30 karakter';
        }

        if ($type !== null && $type !== '' && !in_array($type, ['house','kavling'], true)) {
            $err['type'] = 'Harus house|kavling';
        }
        if ($status !== null && $status !== '' && !in_array($status, ['occupied','vacant','owned','rented','plot','unknown'], true)) {
            $err['status'] = 'Harus occupied|vacant|owned|rented|plot|unknown';
        }

        return $err;
    }

    public function create(array $in): int
    {
        $this->db->insert('houses', [
            'block' => trim((string)$in['block']),
            'number' => trim((string)$in['number']),
            'code' => trim((string)$in['code']),
            'type' => (string)$in['type'],
            'status' => (string)$in['status'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $in): void
    {
        $data = [];
        foreach (['block','number','code','type','status'] as $k) {
            if (array_key_exists($k, $in)) {
                $val = is_string($in[$k]) ? trim($in[$k]) : $in[$k];
                $data[$k] = $val;
            }
        }
        if (!$data) {
            return;
        }
        $this->db->where('id', $id)->update('houses', $data);
    }

    public function delete(int $id): void
    {
        $this->db->where('id', $id)->delete('houses');
    }

    public function has_active_occupancy(int $house_id): bool
    {
        $this->db->from('house_occupancies')
            ->where('house_id', $house_id)
            ->where('status', 'active');
        return (int)$this->db->count_all_results() > 0;
    }

    private function normalize_status_filter(string $status): string
    {
        $s = strtolower(trim($status));
        return in_array($s, ['occupied','vacant','owned','rented','plot','unknown'], true) ? $s : '';
    }

    private function normalize_type_filter(string $type): string
    {
        $t = strtolower(trim($type));
        return in_array($t, ['house','kavling'], true) ? $t : '';
    }

    public function paginate(int $page, int $per, string $q = '', string $status = '', string $type = '', string $status_group = ''): array
    {
        $page = max(1, $page);
        $per = max(1, min(100, $per));
        $offset = ($page - 1) * $per;

        $status = $this->normalize_status_filter($status);
        $type   = $this->normalize_type_filter($type);
        $status_group = strtolower(trim($status_group));

        $qb = $this->db
            ->select("
                h.*,
                (
                    SELECT p.full_name
                    FROM house_ownerships ho
                    JOIN persons p ON p.id = ho.person_id
                    WHERE ho.house_id = h.id
                    AND (ho.end_date IS NULL OR ho.end_date >= CURDATE())
                    ORDER BY ho.start_date DESC, ho.id DESC
                    LIMIT 1
                ) AS owner_name,
                (
                    SELECT p2.full_name
                    FROM house_occupancies oc
                    JOIN households hh ON hh.id = oc.household_id
                    JOIN persons p2 ON p2.id = hh.head_person_id
                    WHERE oc.house_id = h.id
                    AND oc.status = 'active'
                    ORDER BY oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS occupant_name,
                (
                    SELECT oc2.occupancy_type
                    FROM house_occupancies oc2
                    WHERE oc2.house_id = h.id
                    AND oc2.status = 'active'
                    ORDER BY oc2.start_date DESC, oc2.id DESC
                    LIMIT 1
                ) AS occupancy_type
            ", false)
            ->from('houses h');

        if ($q !== '') {
            $qb->group_start()
                ->like('h.code', $q)
                ->or_like('h.block', $q)
                ->or_like('h.number', $q)
                ->group_end();
        }

        if ($status !== '') {
            $qb->where('h.status', $status);
        }
        if ($type !== '') {
            $qb->where('h.type', $type);
        }

        if ($status_group === 'inhabited' && $status === '') {
            $qb->where_in('h.status', ['occupied', 'rented', 'owned']);
        }

        $countQ = clone $qb;
        $total = (int)$countQ->count_all_results('', false);

        $items = $qb
            ->order_by('h.block', 'ASC')
            ->order_by('CAST(h.number AS UNSIGNED)', 'ASC', false)
            ->order_by('h.code', 'ASC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
                'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
                'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
            ],
        ];
    }

    public function paginate_for_household_active_occupancy(int $household_id, int $page, int $per, string $q = '', string $status = '', string $type = '', string $status_group = ''): array
    {
        $page = max(1, $page);
        $per = max(1, min(100, $per));
        $offset = ($page - 1) * $per;

        $status = $this->normalize_status_filter($status);
        $type   = $this->normalize_type_filter($type);
        $status_group = strtolower(trim($status_group));

        $qb = $this->db->select('h.*')
            ->from('house_occupancies ho')
            ->join('houses h', 'h.id = ho.house_id', 'inner')
            ->where('ho.household_id', $household_id)
            ->where('ho.status', 'active');

        if ($q !== '') {
            $qb->group_start()
                ->like('h.code', $q)
                ->or_like('h.block', $q)
                ->or_like('h.number', $q)
                ->group_end();
        }

        if ($status !== '') {
            $qb->where('h.status', $status);
        }
        if ($type !== '') {
            $qb->where('h.type', $type);
        }

        if ($status_group === 'inhabited' && $status === '') {
            $qb->where_in('h.status', ['occupied', 'rented', 'owned']);
        }

        $countQ = clone $qb;
        $total = (int)$countQ->count_all_results('', false);

        $items = $qb->order_by('h.block', 'ASC')
            ->order_by('CAST(h.number AS UNSIGNED)', 'ASC', false)
            ->order_by('h.code', 'ASC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
                'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
                'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
            ],
        ];
    }

}
