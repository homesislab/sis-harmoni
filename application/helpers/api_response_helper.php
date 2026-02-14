<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SIS Paguyuban API Response Helper
 * Standard:
 *  - Success: { ok:true, data:..., meta? }
 *  - Error  : { ok:false, error:{ code, message, fields? } }
 */

if (!function_exists('api_ok')) {
    function api_ok($data = null, array $meta = null, int $http_code = 200): void
    {
        $CI =& get_instance();
        $payload = ['ok' => true, 'data' => $data];
        if ($meta !== null) $payload['meta'] = $meta;

        $CI->output
            ->set_status_header($http_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('api_error')) {
    function api_error(string $code, string $message, int $http_code = 400, array $fields = null): void
    {
        $CI =& get_instance();
        $payload = [
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
        if ($fields !== null) $payload['error']['fields'] = $fields;

        $CI->output
            ->set_status_header($http_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('api_validation_error')) {
    function api_validation_error(array $fields, string $message = 'Validasi gagal'): void
    {
        api_error('VALIDATION_ERROR', $message, 422, $fields);
    }
}

if (!function_exists('api_unauthorized')) {
    function api_unauthorized(string $message = 'Anda belum login'): void
    {
        api_error('UNAUTHENTICATED', $message, 401);
    }
}

if (!function_exists('api_forbidden')) {
    function api_forbidden(string $message = 'Forbidden'): void
    {
        api_error('FORBIDDEN', $message, 403);
    }
}

if (!function_exists('api_not_found')) {
    function api_not_found(string $message = 'Data tidak ditemukan'): void
    {
        api_error('NOT_FOUND', $message, 404);
    }
}

if (!function_exists('api_conflict')) {
    function api_conflict(string $message = 'Konflik data'): void
    {
        api_error('CONFLICT', $message, 409);
    }
}


if (!function_exists('api_bad_request')) {
    function api_bad_request(string $message = 'Request tidak valid'): void
    {
        api_error('BAD_REQUEST', $message, 400);
    }
}

if (!function_exists('api_token_invalid')) {
    function api_token_invalid(string $message = 'Token tidak valid'): void
    {
        api_error('TOKEN_INVALID', $message, 401);
    }
}

if (!function_exists('api_token_expired')) {
    function api_token_expired(string $message = 'Sesi Anda telah berakhir. Silakan login kembali.'): void
    {
        api_error('TOKEN_EXPIRED', $message, 401);
    }
}

if (!function_exists('api_role_forbidden')) {
    function api_role_forbidden(string $message = 'Anda tidak memiliki akses'): void
    {
        api_error('ROLE_FORBIDDEN', $message, 403);
    }
}

if (!function_exists('api_permission_denied')) {
    function api_permission_denied(string $permission, string $message = ''): void
    {
        $msg = $message !== '' ? $message : ("Akses ditolak: permission `{$permission}` diperlukan.");
        api_error('PERMISSION_DENIED', $msg, 403);
    }
}


if (!function_exists('api_pagination_meta')) {
    function api_pagination_meta(int $page, int $per_page, int $total): array
    {
        $total_pages = $per_page > 0 ? (int)ceil($total / $per_page) : 0;
        return [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages,
        ];
    }
}
