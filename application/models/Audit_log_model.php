<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_log_model extends CI_Model
{
    private function extract_id(string $text): ?int
    {
        if (preg_match('/\#(\d+)/', $text, $m)) return (int)$m[1];
        if (preg_match('/\bID\s*(\d+)/i', $text, $m)) return (int)$m[1];
        return null;
    }

    private function humanize_action(string $action, string $description = ''): array
    {
        $a = strtolower(trim($action));
        $id = $this->extract_id($description);

        $fallback = ucwords(str_replace('_', ' ', $a));

        switch ($a) {
            case 'house_claim_approve':
                return [
                    'title' => 'Menyetujui klaim unit',
                    'detail' => $id ? "Klaim unit #{$id} disetujui." : 'Klaim unit disetujui.',
                ];
            case 'house_claim_reject':
                return [
                    'title' => 'Menolak klaim unit',
                    'detail' => $id ? "Klaim unit #{$id} ditolak." : 'Klaim unit ditolak.',
                ];
            case 'post_create':
                return [
                    'title' => 'Membuat pengumuman',
                    'detail' => $id ? "Pengumuman #{$id} dibuat." : 'Pengumuman dibuat.',
                ];
            case 'post_update':
                return [
                    'title' => 'Memperbarui pengumuman',
                    'detail' => $id ? "Pengumuman #{$id} diperbarui." : 'Pengumuman diperbarui.',
                ];
            case 'event_create':
                return [
                    'title' => 'Membuat agenda kegiatan',
                    'detail' => $id ? "Agenda #{$id} dibuat." : 'Agenda kegiatan dibuat.',
                ];
            case 'event_update':
                return [
                    'title' => 'Memperbarui agenda kegiatan',
                    'detail' => $id ? "Agenda #{$id} diperbarui." : 'Agenda kegiatan diperbarui.',
                ];
            case 'invoice_generate':
                return [
                    'title' => 'Menerbitkan tagihan',
                    'detail' => $id ? "Tagihan #{$id} diterbitkan." : 'Tagihan diterbitkan.',
                ];
            case 'payment_approve':
                return [
                    'title' => 'Menyetujui pembayaran',
                    'detail' => $id ? "Pembayaran #{$id} disetujui." : 'Pembayaran disetujui.',
                ];
            case 'payment_reject':
                return [
                    'title' => 'Menolak pembayaran',
                    'detail' => $id ? "Pembayaran #{$id} ditolak." : 'Pembayaran ditolak.',
                ];
            default:
                return [
                    'title' => $fallback,
                    'detail' => $description !== '' ? $description : $fallback,
                ];
        }
    }

    public function paginate(int $page, int $per, array $filters): array
    {
        $offset = ($page - 1) * $per;

        $qb = $this->db->from('audit_logs a')
            ->join('users u', 'u.id = a.user_id', 'left');

        if (!empty($filters['user_id'])) $qb->where('a.user_id', (int)$filters['user_id']);
        if (!empty($filters['action'])) $qb->where('a.action', (string)$filters['action']);

        if (!empty($filters['from'])) $qb->where('a.created_at >=', (string)$filters['from']);
        if (!empty($filters['to'])) $qb->where('a.created_at <=', (string)$filters['to']);

        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);
            $qb->group_start()
                ->like('a.description', $q)
                ->or_like('u.username', $q)
                ->group_end();
        }

        $total = (int)$qb->count_all_results('', false);

        $items = $qb->select('a.*, u.username AS username')
            ->order_by('a.created_at', 'DESC')
            ->limit($per, $offset)
            ->get()->result_array();

        foreach ($items as &$it) {
            $h = $this->humanize_action((string)($it['action'] ?? ''), (string)($it['description'] ?? ''));
            $it['action_title'] = $h['title'];
            $it['description_human'] = $h['detail'];
        }

        return [
            'items' => $items,
            'meta' => ['page'=>$page, 'per_page'=>$per, 'total'=>$total],
            'total_pages' => ($per > 0 ? (int)ceil($total / $per) : 0),
            'has_prev' => ($page > 1),
            'has_next' => ($page < ($per > 0 ? (int)ceil($total / $per) : 0)),
        ];
    }
}
