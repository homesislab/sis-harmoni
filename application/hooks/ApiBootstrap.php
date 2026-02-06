<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApiBootstrap
{
    public function handle(): void
    {
        // Jika jalan via CLI atau server var tidak ada, skip
        if (php_sapi_name() === 'cli') {
            return;
        }

        $CI = function_exists('get_instance') ? get_instance() : null;

        // Ambil URI secara aman (CI uri kalau ada, kalau tidak fallback REQUEST_URI)
        $uri = $this->safeUriString($CI);

        // Hanya apply untuk API v1
        if ($uri === '' || strpos($uri, 'api/v1/') !== 0) {
            return;
        }

        // ---- CORS ----
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // ---- JSON response default ----
        header('Content-Type: application/json; charset=utf-8');

        // ---- Parse JSON body to $_POST (CI3 default tidak parse JSON) ----
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {

            // pastikan input library tersedia
            if ($CI && isset($CI->input)) {
                $raw = $CI->input->raw_input_stream;
            } else {
                $raw = file_get_contents('php://input');
            }

            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $_POST = array_merge($_POST, $json);
                }
            }
        }
    }

    private function safeUriString($CI): string
    {
        // 1) Prefer CI URI jika sudah siap
        if ($CI && isset($CI->uri) && is_object($CI->uri) && method_exists($CI->uri, 'uri_string')) {
            $u = (string) $CI->uri->uri_string();
            return ltrim($u, '/');
        }

        // 2) Fallback dari REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($requestUri === '') {
            return '';
        }

        // Buang query string
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '';

        // Buang leading slash
        $path = ltrim($path, '/');

        // Jika ada index.php di URL, buang supaya konsisten
        if (strpos($path, 'index.php/') === 0) {
            $path = substr($path, strlen('index.php/'));
        } elseif ($path === 'index.php') {
            $path = '';
        }

        return $path;
    }
}
