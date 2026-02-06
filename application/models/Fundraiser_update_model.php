<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fundraiser_update_model extends CI_Model
{
    public function validate_payload(array $in, bool $is_create): array
    {
        $err = [];
        if ($is_create) {
            foreach (['fundraiser_id','title','content'] as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') $err[$f] = 'Wajib diisi';
            }
        }
        if (isset($in['attachments_json']) && $in['attachments_json'] !== null) {
            if (!is_array($in['attachments_json']) && !is_string($in['attachments_json'])) {
                $err['attachments_json'] = 'Harus array atau string JSON';
            }
        }
        return $err;
    }

    public function find_by_id(int $id): ?array
    {
        $row = $this->db->select('u.*, us.username AS created_by_username')
            ->from('fundraiser_updates u')
            ->join('users us', 'us.id = u.created_by', 'left')
            ->where('u.id', $id)
            ->get()->row_array();

        return $row ?: null;
    }

    public function list_by_fundraiser(int $fundraiser_id): array
    {
        return $this->db->select('u.*, us.username AS created_by_username')
            ->from('fundraiser_updates u')
            ->join('users us', 'us.id = u.created_by', 'left')
            ->where('u.fundraiser_id', $fundraiser_id)
            ->order_by('u.created_at','DESC')
            ->get()->result_array();
    }

    public function create(array $in, int $created_by): int
    {
        $attachments = null;
        if (array_key_exists('attachments_json', $in)) {
            $attachments = is_array($in['attachments_json'])
                ? json_encode($in['attachments_json'])
                : (string)$in['attachments_json'];
        }

        $this->db->insert('fundraiser_updates', [
            'fundraiser_id' => (int)$in['fundraiser_id'],
            'title' => trim((string)$in['title']),
            'content' => (string)$in['content'],
            'attachments_json' => $attachments,
            'created_by' => $created_by,
        ]);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $in): void
    {
        $allowed = ['title','content','attachments_json'];
        $upd = [];

        foreach ($allowed as $k) {
            if (!array_key_exists($k, $in)) continue;

            if ($k === 'attachments_json') {
                $upd[$k] = is_array($in[$k]) ? json_encode($in[$k]) : ($in[$k] === null ? null : (string)$in[$k]);
            } else {
                $upd[$k] = is_string($in[$k]) ? trim((string)$in[$k]) : $in[$k];
            }
        }

        if ($upd) $this->db->where('id',$id)->update('fundraiser_updates', $upd);
    }

    public function delete(int $id): void
    {
        $this->db->where('id',$id)->delete('fundraiser_updates');
    }
}
