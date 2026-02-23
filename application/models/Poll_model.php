<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Poll_model extends MY_Model
{
    protected string $table_name = 'polls';

    public function validate_poll(array $in, bool $is_create): array
    {
        $err = [];

        if ($is_create) {
            foreach (['title','start_at','end_at'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                    $err[$f] = 'Wajib diisi';
                }
            }
        }

        if (isset($in['vote_scope'])) {
            $vs = trim((string)$in['vote_scope']);
            if (!in_array($vs, ['user','household'], true)) {
                $err['vote_scope'] = 'Nilai tidak valid';
            }
        }

        if (isset($in['status'])) {
            $st = trim((string)$in['status']);
            if (!in_array($st, ['draft','published','closed'], true)) {
                $err['status'] = 'Nilai tidak valid';
            }
        }

        foreach (['start_at','end_at'] as $k) {
            if (isset($in[$k]) && trim((string)$in[$k]) !== '') {
                if (!preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', trim((string)$in[$k]))) {
                    $err[$k] = 'Format YYYY-MM-DD HH:MM:SS';
                }
            }
        }

        if (isset($in['start_at'], $in['end_at']) && !$err) {
            $sa = trim((string)$in['start_at']);
            $ea = trim((string)$in['end_at']);
            if ($sa !== '' && $ea !== '' && $ea <= $sa) {
                $err['end_at'] = 'End harus lebih besar dari start';
            }
        }

        return $err;
    }

    public function find_poll(int $id): ?array
    {
        $row = $this->db->get_where('polls', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function find_detail(int $id): ?array
    {
        $poll = $this->find_poll($id);
        if (!$poll) {
            return null;
        }

        $options = $this->db->from('poll_options')->where('poll_id', $id)->order_by('id', 'ASC')->get()->result_array();

        return [
            'poll' => $poll,
            'options' => $options,
        ];
    }

    public function paginate(int $page, int $per, array $filters): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('polls p')
            ->join('users u', 'u.id = p.created_by', 'left');

        if (!empty($filters['status'])) {
            $qb->where('p.status', (string)$filters['status']);
        }
        if (!empty($filters['status_in']) && is_array($filters['status_in'])) {
            $qb->where_in('p.status', $filters['status_in']);
        }

        if (!empty($filters['q'])) {
            $q = (string)$filters['q'];
            $qb->group_start()
               ->like('p.title', $q)
               ->or_like('p.description', $q)
               ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('p.id,p.title,p.start_at,p.end_at,p.status,p.vote_scope,p.created_at,u.username AS created_by_username')
            ->order_by('p.created_at', 'DESC')
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

    public function create_poll(array $in, int $created_by): int
    {
        $this->db->insert('polls', [
            'title' => trim((string)$in['title']),
            'description' => isset($in['description']) ? (string)$in['description'] : null,
            'start_at' => trim((string)$in['start_at']),
            'end_at' => trim((string)$in['end_at']),
            'status' => 'draft',
            'vote_scope' => isset($in['vote_scope']) ? trim((string)$in['vote_scope']) : 'user',
            'created_by' => $created_by,
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_poll(int $id, array $in): void
    {
        $allowed = ['title','description','start_at','end_at','vote_scope'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $in)) {
                $upd[$k] = is_string($in[$k]) ? trim((string)$in[$k]) : $in[$k];
            }
        }
        if ($upd) {
            $this->db->where('id', $id)->update('polls', $upd);
        }
    }

    public function delete_poll(int $id): void
    {
        $this->db->where('id', $id)->delete('polls');
    }

    public function set_status(int $id, string $status): void
    {
        $this->db->where('id', $id)->update('polls', ['status' => $status]);
    }

    public function count_options(int $poll_id): int
    {
        return (int)$this->db->from('poll_options')->where('poll_id', $poll_id)->count_all_results();
    }

    public function create_option(int $poll_id, string $label): int
    {
        $this->db->insert('poll_options', [
            'poll_id' => $poll_id,
            'label' => $label,
        ]);
        return (int)$this->db->insert_id();
    }

    public function find_option(int $id): ?array
    {
        $row = $this->db->get_where('poll_options', ['id' => $id])->row_array();
        return $row ?: null;
    }

    public function update_option(int $id, string $label): void
    {
        $this->db->where('id', $id)->update('poll_options', ['label' => $label]);
    }

    public function delete_option(int $id): void
    {
        $this->db->where('id', $id)->delete('poll_options');
    }

    public function resolve_household_id_for_person(int $person_id): ?int
    {
        $row = $this->db->select('household_id')
            ->from('household_members')
            ->where('person_id', $person_id)
            ->order_by('id', 'ASC')
            ->get()->row_array();

        return $row ? (int)$row['household_id'] : null;
    }

    public function get_results(int $poll_id): array
    {
        $poll = $this->find_poll($poll_id);
        $options = $this->db->from('poll_options')->where('poll_id', $poll_id)->order_by('id', 'ASC')->get()->result_array();

        $rows = $this->db->select('option_id, COUNT(*) AS total')
            ->from('poll_votes')
            ->where('poll_id', $poll_id)
            ->group_by('option_id')
            ->get()->result_array();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['option_id']] = (int)$r['total'];
        }

        $total_votes = array_sum($map);

        $items = [];
        foreach ($options as $o) {
            $cnt = $map[(int)$o['id']] ?? 0;
            $pct = $total_votes > 0 ? round(($cnt / $total_votes) * 100, 2) : 0.0;
            $items[] = [
                'option_id' => (int)$o['id'],
                'label' => $o['label'],
                'votes' => $cnt,
                'percent' => $pct,
            ];
        }

        return [
            'poll' => $poll,
            'total_votes' => $total_votes,
            'items' => $items,
        ];
    }
}
