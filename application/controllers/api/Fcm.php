<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Fcm extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
    }

    public function token(): void
    {
        if (!$this->db->table_exists('fcm_tokens')) {
            api_ok(['saved' => false, 'message' => 'Tabel fcm_tokens belum tersedia.']);
            return;
        }

        $in = $this->json_input();
        $token = trim((string)($in['token'] ?? ''));
        $deviceType = trim((string)($in['device_type'] ?? 'web')) ?: 'web';
        $userId = (int)($this->auth_user['id'] ?? 0);

        if ($userId <= 0) {
            api_error('FORBIDDEN', 'Sesi pengguna tidak valid', 403);
            return;
        }

        if ($token === '') {
            api_validation_error(['token' => 'Wajib diisi']);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $existing = $this->db->select('id')
            ->from('fcm_tokens')
            ->where('user_id', $userId)
            ->where('token', $token)
            ->limit(1)
            ->get()
            ->row_array();

        if ($existing) {
            $this->db->where('id', (int)$existing['id'])->update('fcm_tokens', [
                'device_type' => $deviceType,
                'last_active' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $this->db->insert('fcm_tokens', [
                'user_id' => $userId,
                'token' => $token,
                'device_type' => $deviceType,
                'last_active' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        api_ok([
            'saved' => true,
            'token' => $token,
            'device_type' => $deviceType,
        ]);
    }

    public function destroy_token(): void
    {
        if (!$this->db->table_exists('fcm_tokens')) {
            api_ok(['deleted' => true]);
            return;
        }

        $in = $this->json_input();
        $token = trim((string)($in['token'] ?? ''));
        $userId = (int)($this->auth_user['id'] ?? 0);

        if ($userId <= 0) {
            api_error('FORBIDDEN', 'Sesi pengguna tidak valid', 403);
            return;
        }

        if ($token === '') {
            api_validation_error(['token' => 'Wajib diisi']);
            return;
        }

        $this->db->where('user_id', $userId)
            ->where('token', $token)
            ->delete('fcm_tokens');

        api_ok(['deleted' => true]);
    }
}
