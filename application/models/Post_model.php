<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends CI_Model
{
    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];
        if ($is_create) {
            foreach (['title','content'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') $err[$f] = 'Wajib diisi';
            }
        }

        if (isset($in['category'])) {
            $c = trim((string)$in['category']);
            if (!in_array($c, ['umum','keamanan','keuangan','layanan','fasilitas','lingkungan','administrasi','keagamaan','sosial'], true)) $err['category'] = 'Kategori tidak valid';
        }

        if (isset($in['org'])) {
            $o = trim((string)$in['org']);
            if (!in_array($o, ['paguyuban','dkm'], true)) $err['org'] = 'Organisasi tidak valid';
        }

        if (isset($in['status'])) {
            $s = trim((string)$in['status']);
            if (!in_array($s, ['draft','published'], true)) $err['status'] = 'Status tidak valid';
        }

        if (array_key_exists('image_url', $in) && $in['image_url'] !== null) {
            $url = trim((string)$in['image_url']);
            if ($url !== '' && strlen($url) > 255) $err['image_url'] = 'Maks 255 karakter';
        }

        return $err;
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('p.*, u.username AS created_by_username')
            ->from('posts p')
            ->join('users u', 'u.id = p.created_by', 'left')
            ->where('p.id', $id)
            ->get()->row_array();

        return $row ?: null;
    }

    public function create(array $in, int $created_by): int
    {
        $this->db->insert('posts', [
            'title' => trim((string)$in['title']),
            'content' => (string)$in['content'],
            'org' => isset($in['org']) ? trim((string)$in['org']) : 'paguyuban',
            'category' => isset($in['category']) ? trim((string)$in['category']) : 'umum',
            'status' => isset($in['status']) ? trim((string)$in['status']) : 'published',
            'image_url' => array_key_exists('image_url', $in) ? ($in['image_url'] !== null ? trim((string)$in['image_url']) : null) : null,
            'created_by' => $created_by,
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $in): void
    {
        $allowed = ['title','content','org','category','status','image_url'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $in)) {
                $upd[$k] = is_string($in[$k]) ? trim((string)$in[$k]) : $in[$k];
            }
        }
        if ($upd) $this->db->where('id', $id)->update('posts', $upd);
    }

    public function delete(int $id): void
    {
        $this->db->where('id', $id)->delete('posts');
    }

    public function paginate(int $page, int $per, array $filters): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('posts p')
            ->join('users u', 'u.id = p.created_by', 'left');

        if (!empty($filters['org'])) $qb->where('p.org', (string)$filters['org']);
        if (!empty($filters['category'])) $qb->where('p.category', (string)$filters['category']);
        if (!empty($filters['status'])) $qb->where('p.status', (string)$filters['status']);

        if (!empty($filters['q'])) {
            $q = (string)$filters['q'];
            $qb->group_start()
               ->like('p.title', $q)
               ->or_like('p.content', $q)
               ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('p.id, p.title, p.org, p.category, p.status, p.image_url, p.created_at, p.updated_at, u.username AS created_by_username')
            ->order_by('p.created_at', 'DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        return [
            'items' => $items,
            'meta' => ['page'=>$page,'per_page'=>$per,'total'=>$total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
