<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Event_model extends MY_Model
{
    protected string $table_name = 'events';

    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];

        if ($is_create) {
            foreach (['title','event_at','org'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                    $err[$f] = 'Wajib diisi';
                }
            }
        }

        if (isset($in['org'])) {
            $o = trim((string)$in['org']);
            if (!in_array($o, ['paguyuban','dkm'], true)) {
                $err['org'] = 'Nilai tidak valid';
            }
        }

        if (isset($in['event_at'])) {
            $dt = trim((string)$in['event_at']);
            if ($dt !== '' && !preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', $dt)) {
                $err['event_at'] = 'Format YYYY-MM-DD HH:MM:SS';
            }
        }

        if (array_key_exists('image_url', $in) && $in['image_url'] !== null) {
            $url = trim((string)$in['image_url']);
            if ($url !== '' && strlen($url) > 255) {
                $err['image_url'] = 'Maks 255 karakter';
            }
        }

        return $err;
    }

    public function validate_filters(array $filters): array
    {
        $err = [];
        if (!empty($filters['org']) && !in_array($filters['org'], ['paguyuban','dkm'], true)) {
            $err['org'] = 'Nilai tidak valid';
        }
        if (!empty($filters['from']) && !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $filters['from'])) {
            $err['from'] = 'Format YYYY-MM-DD';
        }
        if (!empty($filters['to']) && !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $filters['to'])) {
            $err['to'] = 'Format YYYY-MM-DD';
        }
        return $err;
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('e.*, u.username AS created_by_username')
            ->from('events e')
            ->join('users u', 'u.id = e.created_by', 'left')
            ->where('e.id', $id)
            ->get()->row_array();

        return $row ?: null;
    }

    public function create(array $in, int $created_by): int
    {
        $this->db->insert('events', [
            'title' => trim((string)$in['title']),
            'event_at' => trim((string)$in['event_at']),
            'org' => trim((string)$in['org']),
            'image_url' => array_key_exists('image_url', $in) ? ($in['image_url'] !== null ? trim((string)$in['image_url']) : null) : null,
            'description' => isset($in['description']) ? (string)$in['description'] : null,
            'location' => isset($in['location']) ? trim((string)$in['location']) : null,
            'created_by' => $created_by,
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $in): void
    {
        $allowed = ['title','event_at','org','image_url','description','location'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $in)) {
                $upd[$k] = is_string($in[$k]) ? trim((string)$in[$k]) : $in[$k];
            }
        }
        if ($upd) {
            $this->db->where('id', $id)->update('events', $upd);
        }
    }

    public function delete(int $id): void
    {
        $this->db->where('id', $id)->delete('events');
    }

    public function paginate(int $page, int $per, array $filters): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('events e')
            ->join('users u', 'u.id = e.created_by', 'left');

        if (!empty($filters['org'])) {
            $qb->where('e.org', (string)$filters['org']);
        }

        if (!empty($filters['from'])) {
            $qb->where('DATE(e.event_at) >=', (string)$filters['from']);
        }
        if (!empty($filters['to'])) {
            $qb->where('DATE(e.event_at) <=', (string)$filters['to']);
        }

        if (!empty($filters['q'])) {
            $q = (string)$filters['q'];
            $qb->group_start()
               ->like('e.title', $q)
               ->or_like('e.description', $q)
               ->or_like('e.location', $q)
               ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('e.id, e.title, e.event_at, e.org, e.image_url, e.location, e.created_at, u.username AS created_by_username')
            ->order_by('e.event_at', 'ASC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page' => $page,'per_page' => $per,'total' => $total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
