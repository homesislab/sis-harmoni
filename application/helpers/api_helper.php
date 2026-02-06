<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('api_fail')) {
    function api_fail(string $code, string $message, array $fields = null, int $httpCode = 400): void
    {
        http_response_code($httpCode);
        $payload = [
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ];
        if ($fields !== null) {
            $payload['error']['fields'] = $fields;
        }
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('api_bearer_token')) {
    function api_bearer_token(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (!$header) return null;

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}

if (!function_exists('api_pagination_meta')) {
    function api_pagination_meta(int $page, int $perPage, int $total): array
    {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }
}

if (!function_exists('api_int')) {
    function api_int($val, int $default = 0): int
    {
        if ($val === null || $val === '') return $default;
        return (int)$val;
    }
}

if (!function_exists('audit_log')) {
    /**
     * Simpan audit log.
     * @param CI_Controller $CI
     * @param string $action (lihat enums.md audit_action)
     * @param string|null $description
     */
    function audit_log($CI, string $action, ?string $description = null): void
    {
        try {
            if (!$CI || !isset($CI->db)) return;

            $user_id = null;

            // kalau pakai MY_Controller auth_user
            if (property_exists($CI, 'auth_user') && is_array($CI->auth_user) && !empty($CI->auth_user['id'])) {
                $user_id = (int)$CI->auth_user['id'];
            }

            // fallback: session
            if (!$user_id && $CI->session && $CI->session->userdata('user_id')) {
                $user_id = (int)$CI->session->userdata('user_id');
            }

            if (!$user_id) return; // audit butuh user

            $ip = $CI->input ? $CI->input->ip_address() : null;
            $ua = $CI->input ? (string)$CI->input->user_agent() : null;

            $CI->db->insert('audit_logs', [
                'user_id' => $user_id,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ip,
                'user_agent' => $ua ? substr($ua, 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            // jangan ganggu alur utama
            log_message('error', 'audit_log failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('is_https_url')) {
    function is_https_url(string $url): bool
    {
        $parts = parse_url($url);
        return is_array($parts)
            && !empty($parts['scheme'])
            && strtolower($parts['scheme']) === 'https'
            && !empty($parts['host']);
    }
}

if (!function_exists('validate_proof_url')) {
    /**
     * Validasi proof_file_url:
     * - opsional (boleh null/empty)
     * - jika ada:
     *   - boleh URL local (base_url uploads) meski http (dev)
     *   - boleh https untuk external, optional whitelist
     */
    function validate_proof_url($CI, ?string $url): ?string
    {
        $url = $url !== null ? trim($url) : null;
        if (!$url) return null; // OPSIONAL

        // ---- Allow local uploads (dev bisa http) ----
        // upload endpoint kamu: /uploads/images/...
        $baseImages = rtrim(base_url('uploads/images/'), '/') . '/';
        if (strpos($url, $baseImages) === 0) {
            return null;
        }

        // kalau suatu saat kamu pakai uploads/proofs juga, tetap allow:
        $baseProofs = rtrim(base_url('uploads/proofs/'), '/') . '/';
        if (strpos($url, $baseProofs) === 0) {
            return null;
        }

        // ---- External URL: wajib https + valid ----
        if (!is_https_url($url)) {
            return 'Proof URL harus https dan valid';
        }

        // external whitelist (optional)
        try {
            $CI->config->load('sis_harmoni', true);
            $cfg = $CI->config->item('sis_uploads', 'sis_harmoni');
            $wl  = $cfg['proof_url_whitelist_domains'] ?? [];
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host) return 'Host URL tidak valid';

            if (!empty($wl) && !in_array($host, $wl, true)) {
                return 'Domain proof URL tidak diizinkan';
            }
        } catch (Throwable $e) {
            // kalau config tidak ada, jangan block
        }

        return null;
    }
}