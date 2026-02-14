<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JWT Config (HS256)
 * IMPORTANT:
 * - Ganti secret ini di server production (ENV lebih aman).
 */
$config['jwt_secret'] = ($_ENV['SIS_PAGUYUBAN_JWT_SECRET'] ?? getenv('SIS_PAGUYUBAN_JWT_SECRET')) ?: 'CHANGE_ME_SUPER_SECRET_SIS_PAGUYUBAN';
$config['jwt_issuer'] = ($_ENV['SIS_PAGUYUBAN_JWT_ISS'] ?? getenv('SIS_PAGUYUBAN_JWT_ISS')) ?: 'sis-paguyuban';
$config['jwt_ttl_seconds'] = (int)(($_ENV['SIS_PAGUYUBAN_JWT_TTL'] ?? getenv('SIS_PAGUYUBAN_JWT_TTL')) ?: 86400); // default 24 jam
