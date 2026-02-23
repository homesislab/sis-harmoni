<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Vehicle_model extends MY_Model
{
    protected string $table_name = 'vehicles';

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('vehicles', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];

        if ($is_create) {
            foreach (['person_id','type','plate_number'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                    $err[$f] = 'Wajib diisi';
                }
            }
        }

        if (isset($in['type'])) {
            $t = trim((string)$in['type']);
            if (!in_array($t, ['motor','mobil','lainnya'], true)) {
                $err['type'] = 'Nilai tidak valid';
            }
        }

        return $err;
    }

    public function exists_person_plate(int $person_id, string $plate_number, ?int $exclude_id = null): bool
    {
        $this->db->from('vehicles')
            ->where('person_id', $person_id)
            ->where('plate_number', $plate_number);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return (bool)$this->db->get()->row_array();
    }

    public function create(array $data): int
    {
        $this->db->insert('vehicles', [
            'person_id' => (int)$data['person_id'],
            'type' => $data['type'],
            'plate_number' => trim((string)$data['plate_number']),
            'brand' => $data['brand'] ?? null,
            'color' => $data['color'] ?? null,
            'status' => 'active',
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['type','plate_number','brand','color'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $upd[$k] = is_string($data[$k]) ? trim((string)$data[$k]) : $data[$k];
            }
        }
        if ($upd) {
            $this->db->where('id', $id)->update('vehicles', $upd);
        }
    }

    public function delete(int $id): void
    {
        $this->db->where('id', $id)->delete('vehicles');
    }

    public function paginate_with_household(int $page, int $per, ?int $person_id = null): array
    {
        $offset = ($page - 1) * $per;

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

        $this->db->from('vehicles v');
        if ($person_id) {
            $this->db->where('v.person_id', $person_id);
        }
        $total = (int)$this->db->count_all_results('', false);

        $this->db->select('v.*');
        $this->db->select('hh.id AS household_id');
        $this->db->select('hh.kk_number');
        $this->db->select('hh.head_person_id');

        $this->db->select('h.id AS house_id');
        $this->db->select('h.code AS house_code');
        $this->db->select('p.full_name AS person_name');
        $this->db->select('ph.full_name AS head_person_name');

        $this->db->join('persons p', 'p.id = v.person_id', 'left');

        $this->db->join("($subHm) hmx", "hmx.person_id = v.person_id", "left");
        $this->db->join("household_members hm", "hm.id = hmx.hm_id", "left");
        $this->db->join("households hh", "hh.id = hm.household_id", "left");
        $this->db->join('persons ph', 'ph.id = hh.head_person_id', 'left');

        $this->db->join("($subHo) hox", "hox.household_id = hh.id", "left");
        $this->db->join("house_occupancies ho", "ho.id = hox.ho_id", "left");
        $this->db->join("houses h", "h.id = ho.house_id", "left");

        $items = $this->db
            ->order_by('v.id', 'DESC')
            ->limit($per, $offset)
            ->get()
            ->result_array();

        return [
            'items' => $items,
            'meta' => ['page' => $page,'per_page' => $per,'total' => $total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
