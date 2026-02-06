<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApiCorsHook
{
    public function handle(): void
    {
        // CI instance not available in pre_system reliably; use native globals
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        // Allow all by default (you can restrict later)
        header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit; // stop early for preflight
        }
    }
}
