<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Person_model extends CI_Model
{
    public function find_by_id(int $id): ?array
    {
        $row = $this->db->get_where('persons', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function find_by_nik(string $nik): ?array
    {
        $row = $this->db->get_where('persons', ['nik' => $nik])->row_array();
        return $row ?: null;
    }

    public function validate_payload(array $in, bool $is_create): array
    {
        $req = ['nik','full_name','gender','birth_place','birth_date','religion','marital_status'];
        $err = [];

        if ($is_create) {
            foreach ($req as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') $err[$f] = 'Wajib diisi';
            }
        }

        if (isset($in['gender'])) {
            $g = strtoupper(trim((string)$in['gender']));
            if (!in_array($g, ['M','F'], true)) $err['gender'] = 'Harus M atau F';
        }

        if (isset($in['birth_date'])) {
            $d = trim((string)$in['birth_date']);
            if ($d !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $err['birth_date'] = 'Format YYYY-MM-DD';
        }

        if (array_key_exists('blood_type', $in)) {
            $bt = strtoupper(trim((string)$in['blood_type']));
            if ($bt !== '' && !in_array($bt, ['A','B','AB','O','UNKNOWN'], true)) {
                $err['blood_type'] = 'Nilai tidak valid';
            }
        }

        if (isset($in['marital_status'])) {
            $ms = trim((string)$in['marital_status']);
            if (!in_array($ms, ['single','married','divorced','widowed'], true)) $err['marital_status'] = 'Nilai tidak valid';
        }

        if (isset($in['status'])) {
            $st = trim((string)$in['status']);
            if (!in_array($st, ['active','moved','left'], true)) $err['status'] = 'Nilai tidak valid';
        }

        return array_filter($err, fn($v) => $v !== null);
    }

    public function create(array $data): int
    {
        $insert = [
            'nik'            => trim((string)$data['nik']),
            'full_name'      => trim((string)$data['full_name']),
            'gender'         => strtoupper(trim((string)$data['gender'])),
            'birth_place'    => trim((string)$data['birth_place']),
            'birth_date'     => trim((string)$data['birth_date']),
            'religion'       => trim((string)$data['religion']),
            'blood_type'     => isset($data['blood_type']) && trim((string)$data['blood_type']) !== '' ? strtoupper(trim((string)$data['blood_type'])) : null,
            'marital_status' => trim((string)$data['marital_status']),
            'education'      => $data['education'] ?? null,
            'occupation'     => $data['occupation'] ?? null,
            'phone'          => trim((string)$data['phone']),
            'email'          => $data['email'] ?? null,
            'status'         => $data['status'] ?? 'active',
        ];
        $this->db->insert('persons', $insert);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['nik','full_name','gender','birth_place','birth_date','religion','blood_type','marital_status','education','occupation','phone','email','status'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $v = $data[$k];
                $upd[$k] = is_string($v) ? trim($v) : $v;
                if ($k === 'gender') $upd[$k] = strtoupper(trim((string)$v));
            }
        }
        if ($upd) $this->db->where('id', $id)->update('persons', $upd);
    }

    public function soft_delete(int $id): void
    {
        $this->db->where('id', $id)->update('persons', ['status' => 'left']);
    }

    public function paginate(int $page, int $per, string $q = ''): array
    {
        $page = max(1, $page);
        $per = max(1, min(100, $per));
        $offset = ($page - 1) * $per;

        $qb = $this->db
            ->select("
                p.*,

                -- household_id (terbaru)
                (
                    SELECT hm.household_id
                    FROM household_members hm
                    WHERE hm.person_id = p.id
                    ORDER BY hm.id DESC
                    LIMIT 1
                ) AS household_id,

                -- kk_number
                (
                    SELECT h.kk_number
                    FROM household_members hm2
                    JOIN households h ON h.id = hm2.household_id
                    WHERE hm2.person_id = p.id
                    ORDER BY hm2.id DESC
                    LIMIT 1
                ) AS kk_number,

                -- relationship
                (
                    SELECT hm3.relationship
                    FROM household_members hm3
                    WHERE hm3.person_id = p.id
                    ORDER BY hm3.id DESC
                    LIMIT 1
                ) AS relationship,

                -- unit_code: ambil unit dari occupancy aktif household terbaru (urut terbaru: start_date, id)
                (
                    SELECT hs.code
                    FROM house_occupancies oc
                    JOIN houses hs ON hs.id = oc.house_id
                    WHERE oc.status = 'active'
                    AND oc.household_id = (
                        SELECT hm4.household_id
                        FROM household_members hm4
                        WHERE hm4.person_id = p.id
                        ORDER BY hm4.id DESC
                        LIMIT 1
                    )
                    ORDER BY oc.start_date DESC, oc.id DESC
                    LIMIT 1
                ) AS unit_code
            ", false)
            ->from('persons p');

        if ($q !== '') {
            $qb->group_start()
                ->like('p.full_name', $q)
                ->or_like('p.nik', $q)
                ->or_like('p.phone', $q)
                ->group_end();
        }

        $countQ = clone $qb;
        $total = (int)$countQ->count_all_results('', false);

        $items = $qb->order_by('p.id', 'DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
                'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
                'has_prev' => $page > 1,
                'has_next' => $page < ($per > 0 ? (int)ceil($total / $per) : 0),
            ],
        ];
    }

}
