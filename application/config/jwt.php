<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JWT Config (HS256)
 * IMPORTANT:
 * - Ganti secret ini di server production (ENV lebih aman).
 */
$__sis_env = static function (string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value !== false && $value !== null && $value !== '') return $value;
    return $default;
};

$config['jwt_secret'] = $__sis_env('SIS_HARMONI_JWT_SECRET', 'CHANGE_ME_SUPER_SECRET_SIS_HARMONI');
$config['jwt_issuer'] = $__sis_env('SIS_HARMONI_JWT_ISS', 'sis-harmoni');
$config['jwt_access_ttl_seconds'] = (int) $__sis_env('SIS_HARMONI_JWT_ACCESS_TTL', 43200); // default 12 jam
$config['jwt_refresh_ttl_seconds'] = (int) $__sis_env('SIS_HARMONI_JWT_REFRESH_TTL', 15552000); // default 180 hari
$config['jwt_ttl_seconds'] = (int) $__sis_env('SIS_HARMONI_JWT_TTL', $config['jwt_access_ttl_seconds']); // backward-compatible
