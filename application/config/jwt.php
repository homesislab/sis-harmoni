<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JWT Config (HS256)
 * IMPORTANT:
 * - Ganti secret ini di server production (ENV lebih aman).
 */
$config['jwt_secret'] = getenv('SISH_JWT_SECRET') ?: 'CHANGE_ME_SUPER_SECRET_SIS_HARMONI';
$config['jwt_issuer'] = getenv('SISH_JWT_ISS') ?: 'sis-harmoni';
$config['jwt_ttl_seconds'] = (int)(getenv('SISH_JWT_TTL') ?: 86400); // default 24 jam
